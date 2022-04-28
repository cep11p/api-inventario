<?php

namespace app\models;

use yii\base\Exception;
use \app\models\base\Inventario as BaseInventario;
use yii\db\Query;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "inventario".
 */
class Inventario extends BaseInventario
{
    public $vencido;
    public $cantidad;
    public function behaviors()
    {
        return ArrayHelper::merge(
            parent::behaviors(),
            [
                # custom behaviors
            ]
        );
    }

    public function rules()
    {
        return ArrayHelper::merge(
            parent::rules(),
            [
                ['fecha_vencimiento','date','format' => 'php:Y-m-d'],
            ]
        );
    }
    
    public function setAttributes($values, $safeOnly = true) {
        parent::setAttributes($values, $safeOnly);

        #Fecha Vencimiento
        if(isset($values['fecha_vencimiento'])){
            $this->fecha_vencimiento = (empty($values['fecha_vencimiento'])) ? NULL : $values['fecha_vencimiento'];
        }
        
        if(isset($values['defectuoso'])){
            $this->defectuoso = \app\components\Help::booleanToInt($values['defectuoso']);
        }
        if(isset($values['falta'])){
            $this->falta = \app\components\Help::booleanToInt($values['falta']);
        }

        //sacamos la fecha de vencimiento cuando el producto esta ausente
        if($this->falta == true){
            $this->fecha_vencimiento = null;
        }

    }

    /**
     * Se busca un item en stock
     *
     * @param [array] $item
     * @return array
     */
    static public function buscarItemEnStock($item){

        #Condiciones para
        $item['falta']=0;
        $item['defectuoso']=0;
        $item['inactivo']=0;
        $item['egresoid']=null;

        $producto = Producto::findone(['id' => $item['productoid']])->toArray();
        
        if(empty($producto)){
            throw new Exception("El producto ".$item['productoid']." no existe.");
        }

        $itemsEncontrados = Inventario::find()
            ->where($item)
            ->leftJoin("comprobante as c", "comprobanteid=c.id")
            ->andWhere(['not',['c.approved_at' => null]])
            ->asArray()
            ->all();    

        //chequeamos si hay stock
        if(count($itemsEncontrados)<1){
            throw new Exception("No hay stock del producto ".$producto['nombre']." ".$producto['unidad_valor'].$producto['unidad_medida']." (".$producto['marca'].")");
        } 
        
        return $itemsEncontrados;
    }
    

    /**
     * Nos permite realizar modificacion de stock de un comprobante
     *
     * @param [array] $param
     * @return void
     */
    public function modificarStock($param) {
        $comprobante = Comprobante::findOne(['id' => $param['comprobanteid']]);
        
        if($comprobante == NULL){
            throw new Exception('El comprobante a modificar no existe');
        }

        /**** Nuevo Comprobante *****/
        $comprobante->setAttributesCustom($param);
        if(!$comprobante->save()){
            throw new Exception(json_encode($comprobante->getErrors()));
        }
        
        /** Agregamos al stock un lista de productos **/
        $this->modificarProductoComprobante($comprobante->id, $param);
        
        return $comprobante->id;
    }
    
    /**
     * Vamos a setear los item que resultaron defectuosos. Por lo tanto debemos buscar los productos que cumplan la condicion de busqueda
     * @param array $param
     * @return type
     * @throws Exception
     */
    public function setItemDefectuoso($param) {

        ini_set('memory_limit', '1024M');
        
        if(!isset($param['productoid']) || empty($param['productoid'])){
            throw new Exception('Se requiere el atributo productoid');
        }        

        if(!isset($param['cantidad']) || !is_numeric($param['cantidad']) || intval($param['cantidad'])<=0){
            throw new Exception('La cantidad debe ser un numero mayor a 0');
        }
        
        if(!isset($param['defectuoso'])){
            throw new Exception('El atributo defectuoso es obligatorio');
        }
        $cantidad = $param['cantidad'];

        $condicion['egresoid'] = null;
        $condicion['defectuoso'] = 0;
        $condicion['falta'] = 0;
        $condicion['productoid'] = $param['productoid'];
        $condicion['fecha_vencimiento'] = (isset($param['fecha_vencimiento']) && !empty($param['fecha_vencimiento']))?$param['fecha_vencimiento']:null;
        
        $ids = $this->buscarItem($condicion);

        if(count($ids)<1){
            throw new Exception('No se encontraron productos para modificar');
        } 
        if(isset($cantidad) && count($ids)<$cantidad){
            throw new Exception('La cantidad a modificar es mayor a la cantidad de productos existentes en el inventario ('. count($ids).')');
        } 

        for ($i=0; $i < $param['cantidad']; $i++) { 
            $item = Inventario::findOne(['id' => $ids[$i]]);
            $item->defectuoso = 1;
            $item->save();
        }
        
        $resultado['cantidad'] = $i;

        return $resultado;
    }
    
    /**
     * Obtenemos los ids de los productos
     * @param array $producto_encontroado_lista
     * @return array
     */
    static function buscarItem($condicion) {
        $ids = array();
        $itemsEncontrados = Inventario::find()->where($condicion)->asArray()->all();         
        
        foreach ($itemsEncontrados as $value) {
            $ids[] = $value['id'];
        }
        
        return $ids;
    }
    
    /**
     * Chequeamos si los productos son defectuosos
     * @return boolean
     */
    private function esDefectuoso() {
        $resultado = false;
        if($this->defectuoso == 1){
            $resultado = true;
        }
        
        return $resultado;
    }
    
    /**
     * Chequeamos si los productos son Faltantes
     * @return boolean
     */
    private function esFaltante() {
        $resultado = false;
        if($this->falta == 1){
            $resultado = true;
        }
        
        return $resultado;
    }
    
    private function esVencido() {
        $resultado = false;
        if($this->fecha_vencimiento <= date('Y-m-d') && $this->fecha_vencimiento != null){
            $resultado = true;
        };
        return $resultado;
    }
    
    /**
     * Chequeamos si los productos son adecuados para ser visto como stock
     * @return boolean
     */
    private function esStock() {
        $resultado = false;
        if($this->falta == 0 && $this->defectuoso == 0 && ($this->fecha_vencimiento > date('Y-m-d') || $this->fecha_vencimiento == null)){
            $resultado = true;
        }
        
        return $resultado;
    }
    
    public function fields()
    {
        return ArrayHelper::merge(parent::fields(), [
            'stock'=> function($model){
                return $model->esStock();
            },
            'fecha_vencimiento'=> function($model){
                return ($model->fecha_vencimiento==null)?'':$model->fecha_vencimiento;
            },
            'egresoid'=> function($model){
                return ($model->egresoid==null)?'':$model->egresoid;
            },
            'depositoid'=> function($model){
                return ($model->depositoid==null)?'':$model->depositoid;
            },
            'vencido'=> function($model){
                return $model->esVencido();
            },
            'falta'=> function($model){
                return \app\components\Help::intToBoolean($this->falta);
            },
            'defectuoso'=> function($model){
                return \app\components\Help::intToBoolean($this->defectuoso);
            },
            'por_vencer'=> function($model){
                $fecha_por_vencer = date('Y-m-d',strtotime(date("Y-m-d", strtotime("+10 day"))));
                if(($fecha_por_vencer >= $this->fecha_vencimiento) && ($this->fecha_vencimiento > date('Y-m-d'))){
                    return true;
                }else{
                    return false;
                }
            }
        ]);
        
    }
}
