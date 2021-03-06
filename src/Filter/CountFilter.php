<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineORMAdminBundle\Filter;

use Sonata\AdminBundle\Datagrid\ProxyQueryInterface as BaseProxyQueryInterface;
use Sonata\AdminBundle\Form\Type\Filter\NumberType;
use Sonata\AdminBundle\Form\Type\Operator\NumberOperatorType;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface;
use Symfony\Component\Form\Extension\Core\Type\NumberType as FormNumberType;

final class CountFilter extends Filter
{
    public const CHOICES = [
        NumberOperatorType::TYPE_EQUAL => '=',
        NumberOperatorType::TYPE_GREATER_EQUAL => '>=',
        NumberOperatorType::TYPE_GREATER_THAN => '>',
        NumberOperatorType::TYPE_LESS_EQUAL => '<=',
        NumberOperatorType::TYPE_LESS_THAN => '<',
    ];

    public function filter(BaseProxyQueryInterface $query, $alias, $field, $data)
    {
        /* NEXT_MAJOR: Remove this deprecation and update the typehint */
        if (!$query instanceof ProxyQueryInterface) {
            @trigger_error(sprintf(
                'Passing %s as argument 1 to %s() is deprecated since sonata-project/doctrine-orm-admin-bundle 3.27'
                .' and will throw a \TypeError error in version 4.0. You MUST pass an instance of %s instead.',
                \get_class($query),
                __METHOD__,
                ProxyQueryInterface::class
            ));
        }

        if (!$data || !\is_array($data) || !\array_key_exists('value', $data) || !is_numeric($data['value'])) {
            return;
        }

        $type = $data['type'] ?? NumberOperatorType::TYPE_EQUAL;
        // NEXT_MAJOR: Remove this if and the (int) cast.
        if (!\is_int($type)) {
            @trigger_error(
                'Passing a non integer type is deprecated since sonata-project/doctrine-orm-admin-bundle 3.30'
                .' and will throw a \TypeError error in version 4.0.',
            );
        }
        $operator = $this->getOperator((int) $type);

        // c.name > '1' => c.name OPERATOR :FIELDNAME
        $parameterName = $this->getNewParameterName($query);
        $rootAlias = current($query->getQueryBuilder()->getRootAliases());
        $query->getQueryBuilder()->addGroupBy($rootAlias);
        $this->applyHaving($query, sprintf('COUNT(%s.%s) %s :%s', $alias, $field, $operator, $parameterName));
        $query->getQueryBuilder()->setParameter($parameterName, $data['value']);
    }

    public function getDefaultOptions()
    {
        return [
            'field_type' => FormNumberType::class,
        ];
    }

    public function getRenderSettings()
    {
        return [NumberType::class, [
            'field_type' => $this->getFieldType(),
            'field_options' => $this->getFieldOptions(),
            'label' => $this->getLabel(),
        ]];
    }

    private function getOperator(int $type): string
    {
        if (!isset(self::CHOICES[$type])) {
            // NEXT_MAJOR: Throw an \OutOfRangeException instead.
            @trigger_error(
                'Passing a non supported type is deprecated since sonata-project/doctrine-orm-admin-bundle 3.30'
                .' and will throw an \OutOfRangeException error in version 4.0.',
            );
//            throw new \OutOfRangeException(sprintf(
//                'The type "%s" is not supported, allowed one are "%s".',
//                $type,
//                implode('", "', array_keys(self::CHOICES))
//            ));
        }

        // NEXT_MAJOR: Remove the default value
        return self::CHOICES[$type] ?? self::CHOICES[NumberOperatorType::TYPE_EQUAL];
    }
}
