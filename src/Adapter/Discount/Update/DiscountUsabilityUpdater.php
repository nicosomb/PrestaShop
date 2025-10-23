<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Adapter\Discount\Update;

use CartRule;
use Doctrine\DBAL\Connection;
use PrestaShop\PrestaShop\Core\Domain\Discount\Command\AddDiscountCommand;
use PrestaShop\PrestaShop\Core\Domain\Discount\Command\UpdateDiscountCommand;

class DiscountUsabilityUpdater
{
    public function __construct(
        private readonly Connection $connection,
        private readonly string $dbPrefix
    ) {
    }

    /**
     * Update customer group restrictions for a discount.
     *
     * @param CartRule $discount
     * @param AddDiscountCommand|UpdateDiscountCommand $command
     *
     * @return array List of updated properties
     */
    public function updateCustomerGroups(CartRule $discount, $command): array
    {
        $customerGroupIds = $command->getCustomerGroupIds();

        // First, clean existing customer groups
        $this->cleanCustomerGroups($discount);

        // If there are customer groups, save them
        if (!empty($customerGroupIds)) {
            $discount->group_restriction = true;
            foreach ($customerGroupIds as $groupId) {
                $this->connection->createQueryBuilder()
                    ->insert($this->dbPrefix . 'cart_rule_group')
                    ->values([
                        'id_cart_rule' => (int) $discount->id,
                        'id_group' => $groupId,
                    ])
                    ->executeStatement()
                ;
            }

            return ['group_restriction'];
        }

        // No groups means no restriction
        $discount->group_restriction = false;

        return ['group_restriction'];
    }

    /**
     * Clean all customer groups for a discount.
     *
     * @param CartRule $discount
     *
     * @return void
     */
    private function cleanCustomerGroups(CartRule $discount): void
    {
        $this->connection->createQueryBuilder()
            ->delete($this->dbPrefix . 'cart_rule_group', 'crg')
            ->where('crg.id_cart_rule = :discountId')
            ->setParameter('discountId', (int) $discount->id)
            ->executeStatement()
        ;
    }
}
