<?php

declare(strict_types=1);

namespace Vapewholesaleusa\RewardPointNotifier\Setup\Patch\Schema;

/**
 * Patch for creating the reward_point_notifications table.
 */
class InstallRewardPointNotificationTable implements \Magento\Framework\Setup\Patch\SchemaPatchInterface, \Magento\Framework\Setup\Patch\PatchRevertableInterface
{
    /**
     * @param \Magento\Framework\Setup\SchemaSetupInterface $schemaSetup
     */
    public function __construct(
        private readonly \Magento\Framework\Setup\SchemaSetupInterface $schemaSetup
    ) {
    }

    /**
     * Applies the schema patch.
     *
     * @return void
     * @throws \Zend_Db_Exception
     */
    public function apply(): void
    {
        $this->schemaSetup->startSetup();

        if (!$this->schemaSetup->tableExists('reward_point_notifications')) {
            $table = $this->schemaSetup->getConnection()->newTable(
                $this->schemaSetup->getTable('reward_point_notifications')
            )
                ->addColumn(
                    'id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Notification ID'
                )
                ->addColumn(
                    'data',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    '2M',
                    ['nullable' => false],
                    'Serialized Customer Data'
                )
                ->addColumn(
                    'scheduled_date',
                    \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                    null,
                    ['nullable' => false],
                    'Scheduled Notification Date'
                )
                ->addColumn(
                    'status',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    10,
                    ['nullable' => false, 'default' => 'pending'],
                    'Notification Status'
                )
                ->addColumn(
                    'created_at',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                    'Created At'
                )
                ->addColumn(
                    'updated_at',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE],
                    'Updated At'
                )
                ->setComment('Reward Point Notifications Table');

            $this->schemaSetup->getConnection()->createTable($table);
        }

        $this->schemaSetup->endSetup();
    }

    /**
     * Reverts the schema changes.
     *
     * @return void
     */
    public function revert(): void
    {
        $this->schemaSetup->startSetup();

        if ($this->schemaSetup->tableExists('reward_point_notifications')) {
            $this->schemaSetup->getConnection()->dropTable(
                $this->schemaSetup->getTable('reward_point_notifications')
            );
        }

        $this->schemaSetup->endSetup();
    }

    /**
     * Get dependencies for this patch.
     *
     * @return array
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Get aliases for this patch.
     *
     * @return array
     */
    public function getAliases(): array
    {
        return [];
    }
}
