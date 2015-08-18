<?php

namespace Tests\Data {
    
    /**
     * Test running configuration and save / load.
     */
    class ConfigTest extends \Tests\KnownTestCase  {
        
        
        /**
         * Ensure that config collection has been correctly configured.
         */
        public function testSave() {
            $this->assertNotFalse(\Idno\Core\site()->config()->save());
        }
        
        
        
    }
    
}