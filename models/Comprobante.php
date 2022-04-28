<?php

namespace app\models;

use Yii;
use \app\models\base\Comprobante as BaseComprobante;
use yii\helpers\ArrayHelper;
use yii\base\Exception;
use yii\db\Query;

/**
 * This is the model class for table "comprobante".
 */
class Comprobante extends BaseComprobante
{

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
                # custom validation rules
                [['create_at'],'permitirModificado','on' => 'update']
            ]
        );
    }

    public function permitirModificado(){
        $limit_time = date('Y-m-d H:i:s',strtotime($this->create_at.' +3 hour'));
        if(date('Y-m-d H:i:s') > $limit_time){
            throw new Exception('No se puede editar comprobante despues de 3 horas de su creacion');
        }
    }

    #Registramos los items reutilizando los registros que se puedan
    private function reutilizarRegistros($producto){

        ini_set('memory_limit', '1024M');
        
        $query = new Query();
        
        #set fecha_vencimiento
        $fecha_vencimiento = (isset($producto['fecha_vencimiento']) && !empty($producto['fecha_vencimiento'])) ? $producto['fecha_vencimiento'] : NULL;

        #calculamos la cantidad de registros reutilizables
        $productos_inactivos = Inventario::find()->select(['id'])->where(['inactivo' => 1])->asArray()->all();
        
        #Si la cantidad es negativo se inactivan registros
        if($producto['cantidad']<0){
                
            //necesitamos la cantidad a borrar en numero positvo
            $limit = $producto['cantidad'] * -1;
            
            $lista_ids = Inventario::find()->select('id')->where([
                'comprobanteid' => $this->id,
                'productoid' => $producto['productoid'], 
                'fecha_vencimiento' => $fecha_vencimiento
                ])->limit($limit)->asArray()->all();
                
            Inventario::updateAll(['inactivo'=>1],['id' => $lista_ids]);
            
        #Nuevos registros
        }elseif($producto['cantidad']>=0){
            #### Reutilizamos registros ####
            if(count($productos_inactivos)>$producto['cantidad']){
                
            #procedemos a reutilizar registros
                $limit_reutilizables_ids = Inventario::find()->select(['id'])->where(['inactivo' => 1])->limit($producto['cantidad'])->asArray()->all();
                Inventario::updateAll([
                    'fecha_vencimiento'=>(isset($producto['falta']) &&  $producto['falta'] == 1)?NULL:$fecha_vencimiento,
                    'comprobanteid' => $this->id,
                    'productoid' => $producto['productoid'],
                    'falta' => (!isset($producto['falta']) ||  $producto['falta'] != 1)?0:1,
                    'defectuoso' => (!isset($producto['defectuoso']) || $producto['defectuoso'] != 1)?0:1,
                    'inactivo' => 0
                ],
                ['id' => $limit_reutilizables_ids]);
                
                #### Si los registros reutilizables no bastan
            }else{
                #cantidad reutilizables
                $cant_reutil = count($productos_inactivos);
                
                #si la cantidad reutilizable es mayor a 0, reutilizamos lo que podemos sin problema
                if($cant_reutil>0){
                    #Sacamos la cantidad que se puede reutilzar
                    $limit_reutilizables_ids = Inventario::find()->select(['id'])->where(['inactivo' => 1])->limit($cant_reutil)->asArray()->all();
                    
                    #obtenemos el resto para ser registrados
                    $producto['cantidad'] = $producto['cantidad'] - count($limit_reutilizables_ids);
                    
                    #realizamos la consulta
                    Inventario::updateAll([
                        'fecha_vencimiento'=>(isset($producto['falta']) &&  $producto['falta'] == 1)?NULL:$fecha_vencimiento,
                        'comprobanteid' => $this->id,
                        'productoid' => $producto['productoid'],
                        'falta' => (!isset($producto['falta']) ||  $producto['falta'] != 1)?0:1,
                        'defectuoso' => (!isset($producto['defectuoso']) || $producto['defectuoso'] != 1)?0:1,
                        'inactivo' => 0
                    ],
                    ['id' => $limit_reutilizables_ids]);
                    
                }
                
                $resultado = array();
                #Registramos los items restantes
                if($producto['cantidad']>0){
                    for ($i=0; $i < $producto['cantidad']; $i++) { 
                        
                        $resultado[] = $query->createCommand()->insert('inventario', [
                            'fecha_vencimiento'=>(isset($producto['falta']) &&  $producto['falta'] == 1)?NULL:$fecha_vencimiento,
                            'comprobanteid' => $this->id,
                            'productoid' => $producto['productoid'],
                            'falta' => (!isset($producto['falta']) ||  $producto['falta'] != 1)?0:1,
                            'defectuoso' => (!isset($producto['defectuoso']) || $producto['defectuoso'] != 1)?0:1,
                        ])->execute();
                    }
                }
            }
        }
    }

    /**
     * registramos un nuevo stock
     *
     * @param [array] $param
     * @return array
     */
    public function registrarProductos($param, $modificar = false) {
        
        if(!isset($param['lista_producto']) || count($param['lista_producto'])<=0){
            throw new Exception('Falta lista de productos');
        }

        #validamos la lista de productos
        $this->validarListaItems($param['lista_producto']);

        #recorremos la lista de productos
        foreach ($param['lista_producto'] as $producto) {
            if($modificar == false && (!is_numeric($producto['cantidad']) || intval($producto['cantidad'])<=0)){
                throw new Exception('La cantidad debe ser un numero y mayor a 0');
            }
            $this->reutilizarRegistros($producto);
        }
    }


    /**
     * Se valida la lista de producto que se quiere ingresar
     *
     * @param [array] $lista_items
     * @return void
     */
    private function validarListaItems($lista_producto){

        foreach ($lista_producto as $value) {
            $fecha_vencimiento = (isset($value['fecha_vencimiento']) && empty($value['fecha_vencimiento'])) ? $value['fecha_vencimiento'] : NULL;

            #Validamos el producto a registrar
            $modelValidate = new Inventario();
            $modelValidate->comprobanteid = $this->id;
            #anulamos la fecha_vencimiento si falta producto
            $modelValidate->fecha_vencimiento = (isset($value['falta']) &&  $value['falta'] == 1)?NULL:$fecha_vencimiento;
            $modelValidate->productoid = $value['productoid'];
            $modelValidate->falta = (!isset($value['falta']) ||  $value['falta'] != 1)?0:1;

            if(!$modelValidate->validate()){
                throw new Exception(json_encode($modelValidate->getErrors()));
            }
        }
    }

    /**
     * Se modifican los productos de un comprobante
     *
     * @param [type] $comprobanteid
     * @param [type] $param
     * @return void
     */
    public function modificarProductos($param) {
        if(!isset($param['lista_producto']) || count($param['lista_producto'])<=0){
            throw new Exception('Falta lista de productos');
        }
        
        $lista_producto = $param['lista_producto'];
        $productos_registrados = $this->getListaProducto();
        
        $resultado = [];

        #### Calculamos las cantidades a registrar y a borrar ####
        $i=0;
        foreach ($lista_producto as $item) {
            if(!is_numeric($item['cantidad']) || intval($item['cantidad'])<=0){
                throw new Exception('La cantidad debe ser un entero y mayor a 0');
            }
            
            ##Comparamos con productos registrado
            foreach ($productos_registrados as $key => $prod) {
                $item['falta'] = (!isset($item['falta']))?0:isset($item['falta']);
                $fecha_vencimiento = (isset($item['fecha_vencimiento']) && !empty($item['fecha_vencimiento'])) ? $item['fecha_vencimiento'] : NULL;

                
                if( $prod['productoid']==$item['productoid'] && $prod['fecha_vencimiento']==$fecha_vencimiento && $prod['falta']==$item['falta']){
                    
                    
                    #chequeamos si contamos o descontamos cantidad
                    $cant = $item['cantidad'] - $prod['cantidad'];
                    $item['cantidad'] = $cant;
                    $lista_producto[$i] = $item;
                    
                    #Se descarta los productos a registrar o a modificar
                    unset($productos_registrados[$key]);
                }
            }
            $i++;
        }
        
        $param['lista_producto'] = $lista_producto;
        # seteamos inactivos los productos a borrrar
        $this->setInactivoListaProductoRegistrados($productos_registrados);


        #### Despues de calcular las cantidades especificas procedemos a la
        #### reutlizacion de registros y/o agregado de registros nuevos     ####
        $this->registrarProductos($param, $modificar = true);
        
        return $resultado;
    }

    public function setInactivoListaProductoRegistrados($params){
        $resultado = 0;
        foreach ($params as $value) {
            $fecha_vencimiento = (isset($value['fecha_vencimiento']) && !empty($value['fecha_vencimiento'])) ? $value['fecha_vencimiento'] : NULL;

            $lista_ids = Inventario::find()->select('id')->where([
                'comprobanteid' => $this->id,
                'productoid' => $value['productoid'], 
                'fecha_vencimiento' => $fecha_vencimiento
                ])->asArray()->all();         

            $resultado = $resultado + Inventario::updateAll(['inactivo'=>1],['id' => $lista_ids]);               
        }

        return $resultado;
    }
    
    public function getListaProducto() {
        $inventarioSearch = new InventarioSearch();
        
        $resultado = $inventarioSearch->getListaProductoPorComprobanteid(['comprobanteid'=>$this->id, 'inactivo' => 0]);
        
        return $resultado;
    }
    
    public function getCantidadTotalProducto() {
        $inventarioSearch = new InventarioSearch();
        
        $resultado = $inventarioSearch->getCantitadProducto(['comprobanteid'=>$this->id,'inactivo' => 0]);
        
        return $resultado;
    }
    
    public function setAttributesCustom($values, $safeOnly = true) {
        parent::setAttributes($values, $safeOnly);
    }
    
    public function borrarListaProducto() {
        Inventario::deleteAll(['comprobanteid'=>$this->id]);    
    }
   
    /**
     * Se carga una lista de productos 
     * @param array $param lista de prodcuto que ingresan al inventario
     * @throws Exception
     */
    public function setListaProducto($param) {
        
        if(!isset($param['lista_producto']) || count($param['lista_producto'])<=0){
            throw new Exception('Falta lista de productos');
        }
        
        foreach ($param['lista_producto'] as $producto) {
            if(!is_numeric($producto['cantidad']) || intval($producto['cantidad'])<=0){
                throw new Exception('La cantidad debe ser un numero y mayor a 0');
            }

            /** Guardamos un stock segun la cantidad de cada producto **/
            if(isset($producto['cantidad']) && $producto['cantidad']>0){
                for($i = 1; $i <= $producto['cantidad']; $i++ ){
                    $stock = new Inventario();
                    $stock->setAttributes($producto);
                    $stock->comprobanteid = $this->id;
                    $stock->productoid = $producto['id'];
                    if(!$stock->save()){
                        throw new Exception(json_encode($stock->getErrors()));
                    }
                }                    
            }
        }
    }
    
    /**
     * Se hace una modificacion sobre los productos que faltaban entregar. La modificacion se basa en cambiar falta = 1 (true) a falta = 0 (falso)
     * @param array $param
     * @throws Exception
     */
    public function registrarProductoPendiente($param) {
        
        if(!isset($param['falta'])){
            throw new Exception('El atributo falta es obligatorio');
        }
        
        if(!isset($param['fecha_vencimiento']) || !\app\components\Help::validateDate($param['fecha_vencimiento'], 'Y-m-d')){
            throw new Exception('La fecha es obligatoria y debe tener el formato aaaa-mm-dd');
        }

        if(!isset($param['cantidad']) || !is_numeric($param['cantidad']) || intval($param['cantidad'])<=0){
            throw new Exception('La cantidad es obligatoria y debe ser un numero y mayor a 0');
        }        
        
        $falta = \app\components\Help::setBoolean($param['falta']);
        $encontrarListaCondicion = array(
            'comprobanteid'=> $this->id,
            'productoid'=>$param['productoid'],
            'falta'=>1,
            'egresoid'=>null
        );
        $modificarListaCondicion = ['falta'=>0,'fecha_vencimiento'=>$param['fecha_vencimiento']];
        
        //chequeamos si la falta es verdadero, entonces modificaremos los productos con falta en falso
        if($falta){
            $encontrarListaCondicion['falta']=0;
            $encontrarListaCondicion['fecha_vencimiento']=$param['fecha_vencimiento'];
            $modificarListaCondicion = ['falta'=>1,'fecha_vencimiento'=>null];
        }
        $producto_encontroado_lista = Inventario::find()->where($encontrarListaCondicion)->asArray()->all();
        $ids = $this->getProductoEncontradoIds($producto_encontroado_lista, $param['cantidad']);
                           
        $resultado = Inventario::updateAll($modificarListaCondicion, ['id'=>$ids]);        
    }
    
    /**
     * Obtenemos los ids de los productos a modificar
     * @param array $producto_encontroado_lista
     * @param int $cantidad este atributo nos ayuda a validar la cantidad disponible a modificar
     * @return array
     * @throws Exception
     */
    private function getProductoEncontradoIds($producto_encontroado_lista, $cantidad) {
        //Verificamos la cantidad de productos disponibles a modificar
        if(isset($cantidad) && count($producto_encontroado_lista)<$cantidad){
            throw new Exception('La cantidad a modificar es mayor a la cantidad de productos existentes en el inventario ('. count($producto_encontroado_lista).')');
        }  

        for($i = 0; $i < $cantidad; $i++ ){
            $ids[] = $producto_encontroado_lista[$i]['id'];
        } 
        
        return $ids;
    }
    
    public function fields()
    {
        return ArrayHelper::merge(parent::fields(), [
            'producto_cant_total'=> function($model){
                return $model->cantidadTotalProducto;
            },
            'proveedorid'=> function($model){
                return ($model->proveedorid==null)?'':$model->proveedorid;
            },
            'proveedor'=> function($model){
                return ($model->proveedor==null)?'':$model->proveedor;
            },
            'approved' => function($model){
                return ($model->approved_at == null)?0:1;
            }
        ]);
    }
}
