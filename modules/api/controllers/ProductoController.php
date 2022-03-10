<?php
namespace app\modules\api\controllers;

use yii\rest\ActiveController;
use yii\web\Response;

use Yii;
use yii\base\Exception;

use app\models\Producto;

class ProductoController extends ActiveController{
    
    public $modelClass = 'app\models\Producto';
    
    public function behaviors()
    {

        $behaviors = parent::behaviors();     

        $auth = $behaviors['authenticator'];
        unset($behaviors['authenticator']);

        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::className()
        ];

        $behaviors['contentNegotiator']['formats']['application/json'] = Response::FORMAT_JSON;

        $behaviors['authenticator'] = $auth;

       $behaviors['authenticator'] = [
           'class' => \yii\filters\auth\HttpBearerAuth::className(),
       ];

        // avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
        $behaviors['authenticator']['except'] = ['options'];     

        $behaviors['access'] = [
            'class' => \yii\filters\AccessControl::className(),
            'only' => ['@'],
            'rules' => []
        ];



        return $behaviors;
    }
    
    public function actions()
    {
        $actions = parent::actions();
        unset($actions['create']);
        unset($actions['update']);
//        unset($actions['delete']);
        $actions['index']['prepareDataProvider'] = [$this, 'prepareDataProvider'];
        return $actions;
    }
    
    public function prepareDataProvider() 
    {
        $searchModel = new \app\models\ProductoSearch();
        $params = \Yii::$app->request->queryParams;
        $resultado = $searchModel->search($params);

        return $resultado;
    }
    
    public function actionCreate() 
    {
        #Permiso
        if (!\Yii::$app->user->can('producto_crear')) {
            throw new \yii\web\HttpException(403, 'No se tienen permisos necesarios para ejecutar esta acción');
        }

        $param = Yii::$app->request->post();
        
        $model = new Producto();
        $transaction = Yii::$app->db->beginTransaction();
        try {
            
            $model->setAttributesCustom($param);
            $model->codigo = $model->generarCodigo(4);
            
            if(!$model->save()){
                throw new Exception(json_encode($model->getErrors()));
            }

            $transaction->commit();
            
            $resultado['message']='Se registra un nuevo Producto';
            $resultado['id']=$model->id;
            
            return  $resultado;
           
        }catch (Exception $exc) {
            $transaction->rollBack();
            $mensaje =$exc->getMessage();
            throw new \yii\web\HttpException(400, $mensaje);
        }
    }
    
    public function actionUpdate($id) 
    {          
        $param = Yii::$app->request->post();
        $model = Producto::findOne(['id'=>$id]);
        
        if($model==null){
            throw new Exception(json_encode("El producto $id no existe"));
        }
        $transaction = Yii::$app->db->beginTransaction();
        try {
            
            $model->setAttributes($param);
            
            if(!$model->save()){
                throw new Exception(json_encode($model->getErrors()));
            }

            $transaction->commit();
            
            $resultado['message']='Se modifica una producto';
            $resultado['id']=$model->id;
            
            return  $resultado;
           
        }catch (Exception $exc) {
            $transaction->rollBack();
            $mensaje =$exc->getMessage();
            throw new \yii\web\HttpException(400, $mensaje);
        }
    }
    
    /**
     * Seteamos el atributo "activo"
     * @param int $id
     * @return int $id
     * @throws Exception
     * @throws \yii\web\HttpException
     */
    public function actionSetActivo($id) {
        $param = \Yii::$app->request->post();
        $model = Producto::findOne(['id'=>$id]);

        if($model==null){
            throw new Exception(json_encode('El producto no existe'));
        }        
        
        $transaction = Yii::$app->db->beginTransaction();
        try {            
            $model->setActivo($param);

            $transaction->commit();
            
            $resultado['message']='Se modifica el producto';
            $resultado['id']=$model->id;
            
            return  $resultado;
           
        }catch (Exception $exc) {
            $transaction->rollBack();
            $mensaje =$exc->getMessage();
            throw new \yii\web\HttpException(400, $mensaje);
        }
        
        return $resultado;
    }
}