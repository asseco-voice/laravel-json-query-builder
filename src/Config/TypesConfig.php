<?php

namespace Voice\JsonQueryBuilder\Config;

use Voice\SearchQueryBuilder\Exceptions\SearchException;
use Voice\SearchQueryBuilder\Types\AbstractType;
use Voice\SearchQueryBuilder\Types\GenericType;

class TypesConfig extends SearchConfig
{
    const CONFIG_KEY = 'types';

    public function instantiateType(string $type): AbstractType
    {
        if (!array_key_exists($type, $this->registered)) {
            return new GenericType();
        }

        return new $this->registered[$type];
    }

    public function nameClassMapping()
    {
        $names = $this->getTypeNames();
        $callbacks = $this->registered;

        return array_combine($names, $callbacks);
    }

    public function getTypeNames()
    {
        /**
         * @var AbstractType $type
         */
        return array_map(fn($type) => $type::getTypeName(), $this->registered);
    }

    /**
     * @param string $typeName
     * @return mixed
     * @throws SearchException
     */
    public function getCallbackByTypeName(string $typeName): AbstractType
    {
        $mapping = $this->nameClassMapping();

        if(!array_key_exists($typeName, $mapping)){
            if(!array_key_exists('generic', $mapping)){
                throw new SearchException("[Search] No valid callback for '$typeName' type.");
            }

            return new $mapping['generic'];
        }

        return new $mapping[$typeName];
    }
}
