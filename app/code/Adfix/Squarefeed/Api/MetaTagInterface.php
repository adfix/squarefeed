<?php
/**
 * @package Adfix_Squarefeed
 * @author  Alona Tsarova
 */

namespace Adfix\Squarefeed\Api;

interface MetaTagInterface
{
    /**
     * Save meta content value
     *
     * @param string $metaElement
     * @return array
     */
    public function save($metaElement);

    /**
     * Delete meta content value
     *
     * @return array
     */
    public function delete();
}
