<?php

namespace Trinity\Bundle\SearchBundle;

use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\Naming\PropertyNamingStrategyInterface;

/**
 * {@inheritDoc}
 */
class PassThroughNamingStrategy implements PropertyNamingStrategyInterface
{
    /**
     * @param PropertyMetadata $metadata
     * @return string
     */
    public function translateName(PropertyMetadata $metadata)
    {
        return $metadata->name;
    }
}