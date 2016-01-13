<?php

namespace Trinity\Bundle\SearchBundle;


use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\Naming\PropertyNamingStrategyInterface;

class PassThroughNamingStrategy implements PropertyNamingStrategyInterface
{
    public function translateName(PropertyMetadata $metadata)
    {
        return $metadata->name;
    }
}