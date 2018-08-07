<?php
class VTOBook extends VTO {
    public function __construct() 
    {
    	parent::__construct('book', '_bookrewind_books', [
            'owner' => ['owner', 'user', 'id'],
            'buyer' => ['buyer', 'user', 'id'],
            'category' => ['ID_adozione', 'category', 'id']
        ]);
    }
}