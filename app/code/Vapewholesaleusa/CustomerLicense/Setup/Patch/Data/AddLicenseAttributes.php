<?php
declare(strict_types=1);

namespace Vapewholesaleusa\CustomerLicense\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Entity\Attribute\SetFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AddLicenseAttributes implements DataPatchInterface {

    /**
     * Constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CustomerSetupFactory $customerSetupFactory
     * @param SetFactory $attributeSetFactory
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private CustomerSetupFactory $customerSetupFactory,
        private SetFactory $attributeSetFactory
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $customerSetup = $this->customerSetupFactory->create([
            'setup' => $this->moduleDataSetup
        ]);
        $customerEntity = $customerSetup->getEavConfig()->getEntityType(Customer::ENTITY);
        $attributeSetId = $customerEntity->getDefaultAttributeSetId();

        $attributeSet = $this->attributeSetFactory->create();
        $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);
        /**
         * Business License Expiration
         **/
        $customerSetup->addAttribute(Customer::ENTITY, 'business_license_expiration', [
            'type' => 'datetime',
            'label' => 'Business License Expiration',
            'input' => 'date',
            'required' => false,
            'visible' => true,
            'user_defined' => true,
            'system' => false,
            'position' => 200,
        ]);

        /**
         * Tobacco License Expiration
         **/
        $customerSetup->addAttribute(Customer::ENTITY, 'tobacco_license_expiration', [
            'type' => 'datetime',
            'label' => 'Tobacco License Expiration',
            'input' => 'date',
            'required' => false,
            'visible' => true,
            'user_defined' => true,
            'system' => false,
            'position' => 201,
        ]);


        $attributes = [
            'business_license_expiration',
            'tobacco_license_expiration'
        ];

        foreach ($attributes as $attributeCode) {

            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $attributeCode);

            $attribute->addData([
                'used_in_forms' => [
                    'customer_account_create',
                    'customer_account_edit',
                    'adminhtml_customer'
                ]
            ]);
            $attribute->addData([
                'attribute_set_id' => $attributeSetId,
                'attribute_group_id' => $attributeGroupId

            ]);
            $attribute->save();
        }
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
