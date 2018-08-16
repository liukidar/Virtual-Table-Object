<?php
class VTOUser extends VTO {
    public function __construct() 
    {
    	parent::__construct('user', '_usermng_userlist', [], [
            'book' => ['book', ['get-on' => 'book.owner = user.id']]
        ]);
    }
}