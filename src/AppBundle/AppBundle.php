<?php

namespace AppBundle;

use AppBundle\DependencyInjection\MobilesearchExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AppBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (null === $this->extension) {
            $this->extension = new MobilesearchExtension();
        }
        return $this->extension;
    }
}
