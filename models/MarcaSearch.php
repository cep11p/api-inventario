<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Marca;

/**
* MarcaSearch represents the model behind the search form about `app\models\Marca`.
*/
class MarcaSearch extends Marca
{
    /**
    * @inheritdoc
    */
    public function rules()
    {
        return [
            [['id'], 'integer'],
            [['nombre'], 'safe'],
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
    * Creates data provider instance with search query applied
    *
    * @param array $params
    *
    * @return ActiveDataProvider
    */
    public function search($params)
    {
        $query = Marca::find();   

        #Paginacion Dinamica
        if(!isset($params['pagesize']) || !is_numeric($params['pagesize']) || $params['pagesize']==0){
            $paginacion =false;
        }else{
            $pagesize = intval($params['pagesize']);
            $paginacion = [
                "pagesize"=>$pagesize,
                "page"=>(isset($params['page']) && is_numeric($params['page']))?$params['page']:0
            ];
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => $paginacion
        ]);     

        $this->load($params,'');

        if (!$this->validate()) {
            // uncomment the following line if you do not want to any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
        ]);

        $query->andFilterWhere(['like', 'nombre', $this->nombre]);

        $coleccion = array();
        foreach ($dataProvider->getModels() as $value) {
            $coleccion[] = $value->toArray();
        }

        #Paginacion Dinamica
        if(isset($pagesize)){
            $paginas = ceil($dataProvider->totalCount/$pagesize);           
            $resultado['pagesize']=$pagesize;            
            $resultado['pages']=$paginas;            
            $resultado['total_filtrado']=$dataProvider->totalCount;
            $resultado['resultado']=$coleccion;
        }else{
            $resultado = $coleccion;
        }


        return $resultado;
    }
}