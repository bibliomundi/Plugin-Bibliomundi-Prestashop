<?php

class Validate extends ValidateCore
{
    /**
     * Check for product or category name validity
     *
     * @param string $name Product or category name to validate
     * @return boolean Validity is ok or not
     */
    public static function isCatalogName($name)
    {
        return 1; //Permitir a inserção de ebooks com caracteres especiais como #<>;
    }
}