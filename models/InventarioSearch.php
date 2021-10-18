<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Inventario;

/**
* InventarioSearch represents the model behind the search form about `app\models\Inventario`.
*/
class InventarioSearch extends Inventario
{
    public $categoriaid;
    public $unidad_medidaid;
    public $unidad;
    public $marcaid;
    
    /**
    * @inheritdoc
    */
    public function rules()
    {
        return [
            [['comprobanteid', 'productoid', 'defectuoso', 'egresoid', 'depositoid', 'id', 'falta','categoriaid','unidad_medidaid','unidad','marcaid'], 'integer'],
            [['fecha_vencimiento','cantidad','vencido'], 'safe'],
            [['precio_unitario'], 'number'],
        ];
    }

    /**
    * @inheritdoc
    */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }
    
    /**
     * Obtenemos la cantidad de productos vencidos que en el inventario
     * @return int
     */
    private function cantidadVencidos() {
        $query = new \yii\db\Query();
        
        $query->select([
            'cantidad_vencidos'=>'count(productoid)'
        ]);
        $query->from(['inventario']);
        $query->where(['<=','fecha_vencimiento', date('Y-m-d')]);
        $query->andWhere(['egresoid' => null]);
        $query->andWhere(['falta' => 0]);
        
        $command = $query->createCommand();        
        $rows = $command->queryAll();
        
        $resultado = ($rows[0]['cantidad_vencidos']=='')?0:$rows[0]['cantidad_vencidos'];
                
        return intval($resultado);     
    }
    
    /**
     * Obtenemos la cantidad de productos faltantes en el inventario
     * @return int
     */
    private function cantidadFaltantes() {
        $query = new \yii\db\Query();
        
        $query->select([
            'cantidad_faltantes'=>'count(productoid)'
        ]);
        $query->from(['inventario']);
        $query->where(['falta' => 1]);
        $query->andWhere(['egresoid' => null]);
        
        $command = $query->createCommand();        
        $rows = $command->queryAll();
        $resultado = ($rows[0]['cantidad_faltantes']=='')?0:$rows[0]['cantidad_faltantes'];
                
        return intval($resultado);     
    }
    
    /**
     * Obtenemos la cantidad de productos defectuosos en el inventario
     * @return int
     */
    private function cantidadDefectuosos() {
        $query = new \yii\db\Query();
        
        $query->select([
            'cantidad_defectuosos'=>'count(productoid)'
        ]);
        $query->from(['inventario']);
        $query->where(['defectuoso' => 1]);
        $query->andWhere(['egresoid' => null]);
        
        $command = $query->createCommand();        
        $rows = $command->queryAll();
        $resultado = ($rows[0]['cantidad_defectuosos']=='')?0:$rows[0]['cantidad_defectuosos'];
                
        return intval($resultado);     
    }
    
    /**
     * Obtenemos la cantidad de productos en stock
     * @return int
     */
    private function cantidadStock() {
        $query = new \yii\db\Query();
        
        $query->select([
            'cantidad_stock'=>'count(productoid)'
        ]);
        $query->from(['inventario']);
        $query->where(['defectuoso' => 0]);
        $query->andWhere(['or',
                ['>','fecha_vencimiento', date('Y-m-d')],
                ['fecha_vencimiento' => null]
            ]);
        $query->andWhere(['falta' => 0]);
        $query->andWhere(['egresoid' => null]);
        
        $command = $query->createCommand();        
        $rows = $command->queryAll();
        
        $resultado = ($rows[0]['cantidad_stock']=='')?0:$rows[0]['cantidad_stock'];
                
        return intval($resultado);     
    }

    /**
    * Creates data provider instance with search query applied
    *
    * @param array $params
    *
    * @return ActiveDataProvider
    */
    public function search($params)
    {
        $query = Inventario::find();
        $pagesize = (!isset($params['pagesize']) || !is_numeric($params['pagesize']) || $params['pagesize']==0)?20:intval($params['pagesize']);
        
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $pagesize,
                'page' => (isset($params['page']) && is_numeric($params['page']))?$params['page']:0
            ]
        ]);

        $this->load($params,'');
        
        if (!$this->validate()) {
            // uncomment the following line if you do not want to any records when validation fails
             $query->where('0=1');
            return $dataProvider;
        }
    
        $query->select([
            'inventario.*',
            'cantidad'=>'count(productoid)']);
    
        $query->where(['egresoid' => null]);
        $query->andFilterWhere([
            'id' => $this->id,
            'productoid' => $this->productoid,
            'fecha_vencimiento' => $this->fecha_vencimiento,
            'depositoid' => $this->depositoid,
            'falta' => 0
        ]);
        
        //Join con Producto
        $query->leftJoin("producto as p", "productoid=p.id");        
        $query->andFilterWhere(['p.categoriaid' => $this->categoriaid]);
        $query->andFilterWhere(['p.unidad_medidaid' => $this->unidad_medidaid]);
        $query->andFilterWhere(['p.unidad_valor' => $this->unidad]);
        $query->andFilterWhere(['p.marcaid' => $this->marcaid]);
        
        //*******Custom Sort **********//

        if(isset($params['sort'])){
            //producto.categoria
            if($params['sort']=='categoriaid'){
                $query->orderBy('p.categoriaid ASC');
            }
            if($params['sort']=='-categoriaid'){
                $query->orderBy('p.categoriaid DESC');
            }
            //producto.nombre
            if($params['sort']=='-producto'){
                $query->orderBy('p.nombre DESC');
            }
            //producto.nombre
            if($params['sort']=='producto'){
                $query->orderBy('p.nombre ASC');
            }
            //cantidad
            if($params['sort']=='cantidad'){
                $query->orderBy('cantidad ASC');
            }
        }
        
        
        if($this->defectuoso == 1 && $this->vencido == 'true'){
            $query->andWhere(['or',
                ['defectuoso' => $this->defectuoso],
                ['<=','fecha_vencimiento', date('Y-m-d')]
            ]);
        }else if($this->defectuoso == 1){
            $query->andWhere(['defectuoso' => $this->defectuoso]);
        }else if($this->vencido == 'true'){
            $query->andWhere(['<=','fecha_vencimiento', date('Y-m-d')]);
        }else{ //Todos los productos en stock
            $query->andWhere(['defectuoso' => 0]);
            $query->andWhere(['or',
                ['>','fecha_vencimiento', date('Y-m-d')],
                ['fecha_vencimiento' => null]
            ]);
            $query->andWhere(['falta' => 0]);
            $query->andWhere(['egresoid' => null]);
        }
        
        #### Filtro por rango de fecha ####
        if(isset($params['fecha_desde']) && isset($params['fecha_hasta'])){
            $query->andWhere(['between', 'fecha_vencimiento', $params['fecha_desde'], $params['fecha_hasta']]);
        }else if(isset($params['fecha_desde'])){
            $query->andWhere(['between', 'fecha_vencimiento', $params['fecha_desde'], date('Y-m-d')]);
        }else if(isset($params['fecha_hasta'])){
            $query->andWhere(['between', 'fecha_vencimiento', '1970-01-01', $params['fecha_hasta']]);
        }
        
        $query->groupBy(['fecha_vencimiento','productoid','defectuoso','falta']);

        $coleccion = array();
        foreach ($dataProvider->getModels() as $value) {
            $item = $value->toArray();
            $item['cantidad'] = $value->cantidad;
                        
            $producto = (isset($value->producto)?$value->producto->toArray():['producto'=>[]]);
            
            unset($producto['id']);
            unset($item['id']);

            
            $item = \yii\helpers\ArrayHelper::merge($item, $producto);
            $coleccion[] = $item;
        }
        
        $paginas = ceil($dataProvider->totalCount/$pagesize);           
        $data['pagesize']=$pagesize;            
        $data['pages']=$paginas;            
        $data['total_filtrado']=$dataProvider->totalCount;
        $data['cantidad_vencidos'] = $this->cantidadVencidos();
        $data['cantidad_faltantes'] = $this->cantidadFaltantes();
        $data['cantidad_defectuosos'] = $this->cantidadDefectuosos();
        $data['cantidad_stock'] = $this->cantidadStock();
        $data['resultado']=$coleccion;
        
        return $data;
    }
    
    /**
     * Se arma un listado de item(productos) agrupados por fecha_vencimiento, productoid, falta y defectuoso
     * @param array $params
     * @return array
     */
    public function getListaProductoPorComprobanteid($params)
    {
        $query = Inventario::find();
        
        $dataProvider = new ActiveDataProvider([
            'query' => $query
        ]);

        $this->load($params,'');

        if (!$this->validate()) {
            // uncomment the following line if you do not want to any records when validation fails
             $query->where('0=1');
            return $dataProvider;
        }
    
        $query->select([
            'inventario.*',
            'cantidad'=>'count(productoid)']);
        
        $query->andFilterWhere([
            'comprobanteid' => $this->comprobanteid,
        ]);
        
        $query->groupBy(['fecha_vencimiento','productoid','falta']);
        
        $coleccion = array();
        foreach ($dataProvider->getModels() as $value) {
            $item = $value->toArray();
            $item['cantidad'] = $value->cantidad;
            $item['precio_total'] = $value->cantidad * $value->precio_unitario;
            
            $producto = (isset($value->producto)?$value->producto->toArray():['producto'=>[]]);
            
            unset($producto['id']);
            unset($item['id']);
            unset($item['egresoid']);
            
            $item = \yii\helpers\ArrayHelper::merge($item, $producto);
            $coleccion[] = $item;
        }
                
        return $coleccion;
    }
    
    /**
     * Se arma un listado de los productos que egresaron, agrupando por productoid sin flags (stock, vencido, falta, defectuoso)
     * @param array $params
     * @return ActiveDataProvider
     */
    public function getListaProductoPorEgresoId($params)
    {
        $query = Inventario::find();
        
        $dataProvider = new ActiveDataProvider([
            'query' => $query
        ]);

        $this->load($params,'');

        if (!$this->validate()) {
            // uncomment the following line if you do not want to any records when validation fails
             $query->where('0=1');
            return $dataProvider;
        }
    
        $query->select([
            'inventario.*',
            'cantidad'=>'count(productoid)']);
        
        $query->andFilterWhere([
            'id' => $this->id,
            'comprobanteid' => $this->comprobanteid,
            'depositoid' => $this->depositoid,
            'egresoid' => $this->egresoid
        ]);
        
        $query->groupBy(['productoid']);
        
        $coleccion = array();
        foreach ($dataProvider->getModels() as $value) {
            $item = $value->toArray();
            $item['cantidad'] = $value->cantidad;
            $item['precio_total'] = $value->cantidad * $value->precio_unitario;
            
            $producto = (isset($value->producto)?$value->producto->toArray():['producto'=>[]]);
            
            unset($producto['id']);
            unset($item['id']);
            unset($item['falta']);
            unset($item['stock']);
            unset($item['vencido']);
            unset($item['defectuoso']);
            
            $item = \yii\helpers\ArrayHelper::merge($item, $producto);
            $coleccion[] = $item;
        }
                
        return $coleccion;
    }
    
    public function setEgresoid($params)
    {
        $query = Inventario::find();
        
        $dataProvider = new ActiveDataProvider([
            'query' => $query
        ]);

        $this->load($params,'');

        if (!$this->validate()) {
            // uncomment the following line if you do not want to any records when validation fails
             $query->where('0=1');
            return $dataProvider;
        }
    
        $query->select([
            'inventario.*',
            'cantidad'=>'count(productoid)']);
        
        $query->andFilterWhere([
            'id' => $this->id,
            'comprobanteid' => $this->comprobanteid,
            'depositoid' => $this->depositoid,
            'egresoid' => $this->egresoid
        ]);
        
        $query->groupBy(['productoid']);
        
        $coleccion = array();
        foreach ($dataProvider->getModels() as $value) {
            $item = $value->toArray();
            $item['cantidad'] = $value->cantidad;
            $item['precio_total'] = $value->cantidad * $value->precio_unitario;
            
            $producto = (isset($value->producto)?$value->producto->toArray():['producto'=>[]]);
            
            unset($producto['id']);
            unset($item['id']);
            
            $item = \yii\helpers\ArrayHelper::merge($item, $producto);
            $coleccion[] = $item;
        }
                
        return $coleccion;
    }
    
    /**
     * 
     * @param type $params
     * @return ActiveDataProvider
     */
    public function getCantitadProducto($params)
    {
        $query = Inventario::find();
        
        $dataProvider = new ActiveDataProvider([
            'query' => $query
        ]);

        $this->load($params,'');

        if (!$this->validate()) {
            // uncomment the following line if you do not want to any records when validation fails
             $query->where('0=1');
            return $dataProvider;
        }
    
        $query->select([
            'producto_cant_total'=>'count(productoid)',
        ]);
        
        $query->andFilterWhere([
            'id' => $this->id,
            'comprobanteid' => $this->comprobanteid,
            'depositoid' => $this->depositoid,
            'egresoid' => $this->egresoid
        ]);
        
        $rows = $query->createCommand()->queryAll();
//
//        print_r($query->createCommand()->getSql());die();
        $resultado = array();
        foreach ($rows as $value) {
            $resultado = $value['producto_cant_total'];
        }
                
        return $resultado;
    }
}