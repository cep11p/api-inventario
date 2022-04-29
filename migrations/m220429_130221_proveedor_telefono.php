<?php

use yii\db\Migration;

/**
 * Class m220429_130221_proveedor_telefono
 */
class m220429_130221_proveedor_telefono extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('proveedor','telefono', $this->string(50));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220429_130221_proveedor_telefono cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220429_130221_proveedor_telefono cannot be reverted.\n";

        return false;
    }
    */
}
