<?php
class OS extends FOGController {
    // Table
    public $databaseTable = 'os';
    // Name -> Database field name
    public $databaseFields = array(
        'id'		=> 'osID',
        'name'		=> 'osName',
        'description'	=> 'osDescription'
    );
}
