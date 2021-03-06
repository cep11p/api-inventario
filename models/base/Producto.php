<?php
// This class was automatically generated by a giiant build task
// You should not change it manually as it will be overwritten on next build

namespace app\models\base;

use Yii;

/**
 * This is the base-model class for table "producto".
 *
 * @property integer $id
 * @property string $nombre
 * @property string $codigo
 * @property double $unidad_valor
 * @property integer $unidad_medidaid
 * @property integer $marcaid
 * @property integer $categoriaid
 * @property integer $activo
 *
 * @property \app\models\Categoria $categoria
 * @property \app\models\Inventario[] $inventarios
 * @property \app\models\Marca $marca
 * @property \app\models\UnidadMedida $unidadMedida
 * @property string $aliasModel
 */
abstract class Producto extends \yii\db\ActiveRecord
{



    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'producto';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['nombre', 'unidad_medidaid', 'marcaid', 'categoriaid'], 'required'],
            [['unidad_valor'], 'number'],
            [['unidad_medidaid', 'marcaid', 'categoriaid', 'activo'], 'integer'],
            [['nombre'], 'string', 'max' => 200],
            [['codigo'], 'string', 'max' => 45],
            [['codigo'], 'unique'],
            [['categoriaid'], 'exist', 'skipOnError' => true, 'targetClass' => \app\models\Categoria::className(), 'targetAttribute' => ['categoriaid' => 'id']],
            [['marcaid'], 'exist', 'skipOnError' => true, 'targetClass' => \app\models\Marca::className(), 'targetAttribute' => ['marcaid' => 'id']],
            [['unidad_medidaid'], 'exist', 'skipOnError' => true, 'targetClass' => \app\models\UnidadMedida::className(), 'targetAttribute' => ['unidad_medidaid' => 'id']]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'nombre' => 'Nombre',
            'codigo' => 'Codigo',
            'unidad_valor' => 'Unidad Valor',
            'unidad_medidaid' => 'Unidad Medidaid',
            'marcaid' => 'Marcaid',
            'categoriaid' => 'Categoriaid',
            'activo' => 'Activo',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategoria()
    {
        return $this->hasOne(\app\models\Categoria::className(), ['id' => 'categoriaid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInventarios()
    {
        return $this->hasMany(\app\models\Inventario::className(), ['productoid' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMarca()
    {
        return $this->hasOne(\app\models\Marca::className(), ['id' => 'marcaid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUnidadMedida()
    {
        return $this->hasOne(\app\models\UnidadMedida::className(), ['id' => 'unidad_medidaid']);
    }




}
