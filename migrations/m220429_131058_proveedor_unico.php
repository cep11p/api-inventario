<?php

use yii\db\Migration;

/**
 * Class m220429_131058_proveedor_unico
 */
class m220429_131058_proveedor_unico extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('proveedor','nombre', $this->string(100)->unique());
        $this->alterColumn('proveedor','cuit', $this->string(45)->unique());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220429_131058_proveedor_unico cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220429_131058_proveedor_unico cannot be reverted.\n";

        return false;
    }
    */
}
