<?php

namespace Tests\Core {
    
    /**
     * Test session handling code.
     */
    class SessionTest extends \Tests\KnownTestCase  {
        
        /**
         * Test user login/logout.
         * Primarily to test the session code in the DataConciege.
         */
        public function testLoginOut() {
            
            $user = $this->user();
            
            // Have we created a user?
            $this->assertTrue(is_object($user));
            
            // Has logon reported ok?
            $this->assertTrue(is_object(\Idno\Core\site()->session()->logUserOn($user)));
            
            // Verify logon
            $this->assertEquals($_SESSION['user_uuid'], $user->getUUID());
            $this->assertTrue(is_object(\Idno\Core\site()->session()->currentUser()));
            
            //Verify logoff
            \Idno\Core\site()->session()->logUserOff();
            
            $this->assertTrue(empty($_SESSION['user_uuid']));
            $this->assertFalse(is_object(\Idno\Core\site()->session()->currentUser()));
        }
        
        
        public static function tearDownAfterClass() {
            
            \Idno\Core\site()->session()->logUserOff();
            
            // Delete users, if we've created some but forgot to clean up
            if (\Tests\KnownTestCase::$testUser) \Tests\KnownTestCase::$testUser->delete();
        }
    }
    
}