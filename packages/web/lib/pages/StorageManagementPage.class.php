<?php
class StorageManagementPage extends FOGPage {
    // Base variables
    public $node = 'storage';
    public function __construct($name = '') {
        $this->name = 'Storage Management';
        parent::__construct($this->name);
        $this->menu = array(
            '' => $this->foglang[AllSN],
            'add-storage-node' => $this->foglang[AddSN],
            'storage-group' => $this->foglang[AllSG],
            'add-storage-group' => $this->foglang[AddSG],
        );
        if (in_array($_REQUEST[sub],array('edit','delete','delete-storage-node')) && $_REQUEST[id]) {
            $this->obj = $this->getClass('StorageNode',$_REQUEST[id]);
            $this->subMenu = array(
                "?node={$this->node}&sub={$_REQUEST[sub]}&id={$_REQUEST[id]}" => $this->foglang[General],
                "?node={$this->node}&sub=delete-storage-node&id={$_REQUEST[id]}" => $this->foglang[Delete],
            );
            $this->notes = array(
                "{$this->foglang[Storage]} {$this->foglang[Node]}" => $this->obj->get('name'),
                $this->foglang[ImagePath] => $this->obj->get('path'),
                $this->foglang[FTPPath] => $this->obj->get('ftppath'),
            );
        } else if (in_array($_REQUEST[sub],array('edit-storage-group','delete-storage-group')) && $_REQUEST[id]) {
            $this->obj = $this->getClass('StorageGroup',$_REQUEST[id]);
            $this->subMenu = array(
                "?node={$this->node}&sub={$_REQUEST[sub]}&id={$_REQUEST[id]}" => $this->foglang[General],
                "?node={$this->node}&sub=delete-storage-group&id={$_REQUEST[id]}" => $this->foglang[Delete],
            );
            $this->notes = array(
                "{$this->foglang[Storage]} {$this->foglang[Group]}" => $this->obj->get('name'),
            );
        }
    }

    // Common functions - call Storage Node functions if the default sub's are used
    public function search() {
        $this->index();
    }
    public function edit() {
        $this->edit_storage_node();
    }
    public function edit_post() {
        $this->edit_storage_node_post();
    }
    public function delete() {
        $this->delete_storage_node();
    }
    public function delete_post() {
        $this->delete_storage_node_post();
    }
    // Pages
    public function index() {
        // Set title
        $this->title = $this->foglang[AllSN];
        // Find data
        $StorageNodes = $this->getClass(StorageNodeManager)->find();
        // Row data
        foreach ((array)$StorageNodes AS $i => &$StorageNode) {
            $StorageGroup = $this->getClass(StorageGroup,$StorageNode->get(storageGroupID));
            $this->data[] = array_merge(
                (array)$StorageNode->get(),
                array(
                    isMasterText=>($StorageNode->get(isMaster)?'Yes':'No'),
                    isEnabledText=>($StorageNode->get(isEnabled)?'Yes':'No'),
                    isGraphEnabledText=>($StorageNode->get(isGraphEnabled) ? 'Yes' : 'No'),
                    storage_group=>$StorageGroup->get(name),
                )
            );
        }
        unset($StorageNode);
        // Header row
        $this->headerData = array(
            $this->foglang[SN],
            $this->foglang[SG],
            $this->foglang[Enabled],
            $this->foglang[GraphEnabled],
            $this->foglang[MasterNode],
            ''
        );
        // Row templates
        $this->templates = array(
            sprintf('<a href="?node=%s&sub=edit&%s=${id}" title="%s">${name}</a>', $this->node, $this->id, $this->foglang[Edit]),
            sprintf('${storage_group}',$this->node,$this->id),
            sprintf('${isEnabledText}',$this->node,$this->id),
            sprintf('${isGraphEnabledText}',$this->node,$this->id),
            sprintf('${isMasterText}',$this->node,$this->id),
            sprintf('<a href="?node=%s&sub=edit&%s=${id}" title="%s"><i class="icon fa fa-pencil"></i></a> <a href="?node=%s&sub=delete&%s=${id}" title="%s"><i class="icon fa fa-minus-circle"></i></a>',$this->node,$this->id,$this->foglang[Edit],$this->node,$this->id,$this->foglang['Delete'])
        );
        // Row attributes
        $this->attributes = array(
            array(),
            array(),
            array('class'=>c,width=>90),
            array('class'=>c,width=>90),
            array('class'=>c,width=>90),
            array('class'=>c,width=>50),
        );
        // Hook
        $this->HookManager->processEvent(STORAGE_NODE_DATA,array(headerData=>&$this->headerData,data=>&$this->data,templates=>&$this->templates,attributes=>&$this->attributes));
        // Output
        $this->render();
    }
    // STORAGE NODE
    public function add_storage_node() {
        // Set title
        $this->title = $this->foglang[AddSN];
        // Header Data
        unset($this->headerData);
        // Attributes
        $this->attributes = array(
            array(),
            array(),
        );
        // Templates
        $this->templates = array(
            '${field}',
            '${input}',
        );
        // Fields
        $fields = array(
            $this->foglang[SNName] => '<input type="text" name="name" value="${node_name}" autocomplete="off" />*',
            $this->foglang[SNDesc] => '<textarea name="description" rows="8" cols="40" autocomplete="off">${node_desc}</textarea>',
            $this->foglang[IPAdr] => '<input type="text" name="ip" value="${node_ip}" autocomplete="off" />*',
            _('Web root')  => '<input type="text" name="webroot" value="${node_webroot}" autocomplete="off" />*',
            $this->foglang[MaxClients] => '<input type="text" name="maxClients" value="${node_maxclient}" autocomplete="off" />*',
            $this->foglang[IsMasterNode] => '<input type="checkbox" name="isMaster" value="1" />&nbsp;&nbsp;${span}',
            $this->foglang[BandwidthReplication].' (Kbps)' => '<input type="text" name="bandwidth" value="${node_bandwidth}" autocomplete="off" />&nbsp;&nbsp;${span2}',
            $this->foglang[SG] => '${node_group}',
            $this->foglang[ImagePath] => '<input type="text" name="path" value="${node_path}" autocomplete="off" />',
            $this->foglang[FTPPath] => '<input type="text" name="ftppath" value="${node_ftppath}" autocomplete="off" />',
            $this->foglang[SnapinPath] => '<input type="text" name="snapinpath" value="${node_snapinpath}" autocomplete="off" />',
            _('Bitrate') => '<input type="text" name="bitrate" value="${node_bitrate}" autocomplete="off" />',
            $this->foglang['Interface'] => '<input type="text" name="interface" value="${node_interface}" autocomplete="off" />',
            $this->foglang[IsEnabled] => '<input type="checkbox" name="isEnabled" checked value="1" />',
            $this->foglang[IsGraphEnabled].'<br /><small>('.$this->foglang[OnDash].')'  => '<input type="checkbox" name="isGraphEnabled" checked value="1" />',
            $this->foglang[ManUser] => '<input type="text" name="user" value="${node_user}" autocomplete="off" />*',
            $this->foglang[ManPass] => '<input type="password" name="pass" value="${node_pass}" autocomplete="off" />*',
            '<input type="hidden" name="add" value="1" />' => '<input type="submit" value="'.$this->foglang[Add].'" autocomplete="off" />',
        );
        print '<form method="post" action="'.$this->formAction.'">';
        foreach((array)$fields AS $field => &$input) {
            $this->data[] = array(
                field=>$field,
                input=>$input,
                node_name=>$_REQUEST[name],
                node_desc=>$_REQUEST[description],
                node_ip=>$_REQUEST[ip],
                node_webroot=>$_REQUEST[webroot],
                node_maxclient=>$_REQUEST[maxClients]?$_REQUEST[maxClients]:10,
                span=>'<i class="icon fa fa-question hand" title="'.$this->foglang[CautionPhrase].'"></i>',
                span2=>'<i class="icon fa fa-question hand" title="'.$this->foglang[BandwidthRepHelp].'"></i>',
                node_group=>$this->getClass(StorageGroupManager)->buildSelectBox(1, storageGroupID),
                node_path=>$_REQUEST[path]?$_REQUEST[path]:'/images/',
                node_ftppath=>$_REQUEST[ftppath]?$_REQUEST[ftppath]:'/images/',
                node_snapinpath=>$_REQUEST[snapinpath]?$_REQUEST[snapinpath]:'/opt/fog/snapins/',
                node_bitrate=>$_REQUEST[bitrate],
                node_interface=>$_REQUEST['interface'] ? $_REQUEST['interface'] : 'eth0',
                node_user=>$_REQUEST[user],
                node_pass=>$_REQUEST[pass],
                node_bandwidth=>$_REQUEST[bandwidth],
            );
        }
        unset($input);
        // Hook
        $this->HookManager->processEvent(STORAGE_NODE_ADD,array(headerData=>&$this->headerData,data=>&$this->data,templates=>&$this->templates,attributes=>&$this->attributes));
        // Output
        $this->render();
        print "</form>";
    }
    public function add_storage_node_post() {
        // Hook
        $this->HookManager->processEvent(STORAGE_NODE_ADD_POST);
        // POST
        try {
            // Error checking
            if (empty($_REQUEST[name])) throw new Exception($this->foglang[StorageNameRequired]);
            if ($this->getClass(StorageNodeManager)->exists($_REQUEST[name])) throw new Exception($this->foglang[StorageNameExists]);
            if (empty($_REQUEST[ip])) throw new Exception($this->foglang[StorageIPRequired]);
            if (empty($_REQUEST[maxClients])) throw new Exception($this->foglang[StorageClientsRequired]);
            if (empty($_REQUEST['interface'])) throw new Exception($this->foglang[StorageIntRequired]);
            if (empty($_REQUEST[user])) throw new Exception($this->foglang[StorageUserRequired]);
            if (empty($_REQUEST[pass])) throw new Exception($this->foglang[StoragePassRequired]);
            if (((is_numeric($_REQUEST[bandwidth]) && $_REQUEST[bandwidth] <= 0) || !is_numeric($_REQUEST[bandwidth])) && $_REQUEST[bandwidth]) throw new Exception(_('Bandwidth should be numeric and greater than 0'));
            $StorageNode = $this->getClass(StorageNode)
                ->set(name,$_REQUEST[name])
                ->set(description,$_REQUEST[description])
                ->set(ip,$_REQUEST[ip])
                ->set(webroot,$_REQUEST[webroot])
                ->set(maxClients,$_REQUEST[maxClients])
                ->set(isMaster,(int)isset($_REQUEST[isMaster]))
                ->set(storageGroupID,$_REQUEST[storageGroupID])
                ->set(path,$_REQUEST[path])
                ->set(ftppath,$_REQUEST[ftppath])
                ->set(snapinpath,$_REQUEST[snapinpath])
                ->set(bitrate, $_REQUEST[bitrate])
                ->set('interface',$_REQUEST['interface'])
                ->set(isGraphEnabled,(int)isset($_REQUEST[isGraphEnabled]))
                ->set(isEnabled,(int)isset($_REQUEST[isEnabled]))
                ->set(user,$_REQUEST[user])
                ->set(pass,$_REQUEST[pass])
                ->set(bandwidth,$_REQUEST[bandwidth]);
            // Save
            if (!$StorageNode->save()) throw new Exception($this->foglang[DBupfailed]);
            if ($StorageNode->get(isMaster)) {
                // Unset other Master Nodes in this Storage Group
                $Nodes = $this->getClass(StorageNodeManager)->find(array(isMaster=>1,storageGroupID=>$StorageNode->get(storageGroupID)));
                foreach ($Nodes AS $i => &$StorageNodeMaster) {
                    if ($StorageNode->get(id) != $StorageNodeMaster->get(id)) $StorageNodeMaster->set(isMaster,0)->save();
                }
                unset($StorageNodeMaster);
            }
            // Hook
            $this->HookManager->processEvent(STORAGE_NODE_ADD_SUCCESS,array(StorageNode=>&$StorageNode));
            // Log History event
            $this->FOGCore->logHistory(sprintf('%s: ID: %s, Name: %s',$this->foglang[SNCreated],$StorageNode->get(id),$StorageNode->get(name)));
            // Set session message
            $this->FOGCore->setMessage($this->foglang[SNCreated]);
            // Redirect to new entry
            $this->FOGCore->redirect(sprintf('?node=%s',$_REQUEST[node],$this->id, $StorageNode->get(id)));
        } catch (Exception $e) {
            // Hook
            $this->HookManager->processEvent(STORAGE_NODE_ADD_FAIL,array(StorageNode=>&$StorageNode));
            // Log History event
            $this->FOGCore->logHistory(sprintf('%s add failed: Name: %s, Error: %s',$this->foglang[SN],$_REQUEST[name], $e->getMessage()));
            // Set session message
            $this->FOGCore->setMessage($e->getMessage());
            // Redirect to new entry
            $this->FOGCore->redirect($this->formAction);
        }
    }
    public function edit_storage_node() {
        // Find
        $StorageNode = $this->obj;
        // Title
        $this->title = sprintf('%s: %s',$this->foglang[Edit],$StorageNode->get(name));
        // Header Data
        unset($this->headerData);
        // Attributes
        $this->attributes = array(
            array(),
            array(),
        );
        // Templates
        $this->templates = array(
            '${field}',
            '${input}',
        );
        // Fields
        $fields = array(
            $this->foglang[SNName] => '<input type="text" name="name" value="${node_name}" autocomplete="off" />*',
            $this->foglang[SNDesc] => '<textarea name="description" rows="8" cols="40" autocomplete="off">${node_desc}</textarea>',
            $this->foglang[IPAdr] => '<input type="text" name="ip" value="${node_ip}" autocomplete="off" />*',
            _('Web root')  => '<input type="text" name="webroot" value="${node_webroot}" autocomplete="off" />*',
            $this->foglang[MaxClients] => '<input type="text" name="maxClients" value="${node_maxclient}" autocomplete="off" />*',
            $this->foglang[IsMasterNode] => '<input type="checkbox" name="isMaster" value="1" ${ismaster} autocomplete="off" />&nbsp;&nbsp;${span}',
            $this->foglang[BandwidthReplication].'  (Kbps)' => '<input type="text" name="bandwidth" value="${node_bandwidth}" autocomplete="off" />&nbsp;&nbsp;${span2}',
            $this->foglang[SG] => '${node_group}',
            $this->foglang[ImagePath] => '<input type="text" name="path" value="${node_path}" autocomplete="off"/>',
            $this->foglang[FTPPath] => '<input type="text" name="ftppath" value="${node_ftppath}" autocomplete="off"/>',
            $this->foglang[SnapinPath] => '<input type="text" name="snapinpath" value="${node_snapinpath}" autocomplete="off"/>',
            _('Bitrate') => '<input type="text" name="bitrate" value="${node_bitrate}" autocomplete="off" />',
            $this->foglang['Interface'] => '<input type="text" name="interface" value="${node_interface}" autocomplete="off"/>',
            $this->foglang[IsEnabled] => '<input type="checkbox" name="isEnabled" value="1" ${isenabled}/>',
            $this->foglang[IsGraphEnabled].'<br /><small>('.$this->foglang['OnDash'].')'  => '<input type="checkbox" name="isGraphEnabled" value="1" ${graphenabled} />',
            $this->foglang[ManUser] => '<input type="text" name="user" value="${node_user}" autocomplete="off" />*',
            $this->foglang[ManPass] => '<input type="password" name="pass" value="${node_pass}" autocomplete="off" />*',
            '&nbsp;' => '<input type="submit" name="update" value="'.$this->foglang[Update].'" />',
        );
        print '<form method="post" action="'.$this->formAction.'">';
        foreach((array)$fields AS $field => &$input) {
            $this->data[] = array(
                field=>$field,
                input=>$input,
                node_name=>$StorageNode->get(name),
                node_desc=>$StorageNode->get(description),
                node_ip=>$StorageNode->get(ip),
                node_webroot=>$StorageNode->get(webroot),
                node_maxclient=>$StorageNode->get(maxClients),
                ismaster=>$StorageNode->get(isMaster) == 1 ? 'checked' : '',
                isenabled=>$StorageNode->get(isEnabled) == 1 ? 'checked' : '',
                graphenabled=>$StorageNode->get(isGraphEnabled) == 1 ? 'checked' : '',
                span=>'<i class="icon fa fa-question hand" title="'.$this->foglang[CautionPhrase].'"></i>',
                span2=>'<i class="icon fa fa-question hand" title="'.$this->foglang[BandwidthRepHelp].'"></i>',
                node_group=>$this->getClass(StorageGroupManager)->buildSelectBox($StorageNode->get(storageGroupID),storageGroupID),
                node_bandwidth=>$StorageNode->get(bandwidth),
                node_path=>$StorageNode->get(path),
                node_ftppath=>$StorageNode->get(ftppath),
                node_snapinpath=>$StorageNode->get(snapinpath),
                node_bitrate=>$StorageNode->get(bitrate),
                node_interface=>$StorageNode->get('interface'),
                node_user=>$StorageNode->get(user),
                node_pass=>$StorageNode->get(pass),
            );
        }
        unset($input);
        // Hook
        $this->HookManager->processEvent(STORAGE_NODE_EDIT,array(headerData=>&$this->headerData,data=>&$this->data,templates=>&$this->templates,attributes=>&$this->attributes));
        // Output
        $this->render();
        print "</form>";
    }
    public function edit_storage_node_post() {
        // Find
        $StorageNode = $this->obj;
        // Hook
        $this->HookManager->processEvent(STORAGE_NODE_EDIT_POST,array(StorageNode=>&$StorageNode));
        // POST
        try {
            // Error checking
            if (empty($_REQUEST[name])) throw new Exception($this->foglang[StorageNameRequired]);
            if ($this->getClass(StorageNodeManager)->exists($_REQUEST[name],$StorageNode->get(id))) throw new Exception($this->foglang[StorageNameExists]);
            if (empty($_REQUEST[ip])) throw new Exception($this->foglang[StorageIPRequired]);
            if (!is_numeric($_REQUEST[maxClients]) || $_REQUEST[maxClients] < 0) throw new Exception($this->foglang[StorageClientRequired]);
            if (empty($_REQUEST['interface'])) throw new Exception($this->foglang[StorageIntRequired]);
            if (empty($_REQUEST[user])) throw new Exception($this->foglang[StorageUserRequired]);
            if (empty($_REQUEST[pass])) throw new Exception($this->foglang[StoragePassRequired]);
            if (((is_numeric($_REQUEST[bandwidth]) && $_REQUEST[bandwidth] <= 0) || !is_numeric($_REQUEST[bandwidth])) && $_REQUEST[bandwidth]) throw new Exception(_('Bandwidth should be numeric and greater than 0'));
            // Update Object
            $StorageNode
                ->set(name,$_REQUEST[name])
                ->set(description,$_REQUEST[description])
                ->set(ip,$_REQUEST[ip])
                ->set(webroot,$_REQUEST[webroot])
                ->set(maxClients,$_REQUEST[maxClients])
                ->set(isMaster,(int)isset($_REQUEST[isMaster]))
                ->set(storageGroupID,$_REQUEST[storageGroupID])
                ->set(path,$_REQUEST[path])
                ->set(ftppath,$_REQUEST[ftppath])
                ->set(snapinpath,$_REQUEST[snapinpath])
                ->set(bitrate,$_REQUEST[bitrate])
                ->set('interface',$_REQUEST['interface'])
                ->set(isGraphEnabled,(int)isset($_REQUEST[isGraphEnabled]))
                ->set(isEnabled,(int)isset($_REQUEST[isEnabled]))
                ->set(user,$_REQUEST[user])
                ->set(pass,$_REQUEST[pass])
                ->set(bandwidth,$_REQUEST[bandwidth]);
            // Save
            if ($StorageNode->save()) {
                if ($StorageNode->get(isMaster)) {
                    $Nodes = $this->getClass(StorageNodeManager)->find(array(isMaster=>1,storageGroupID=>$StorageNode->get(storageGroupID)));
                    // Unset other Master Nodes in this Storage Group
                    foreach ($Nodes AS $i => &$StorageNodeMaster) {
                        if ($StorageNode->get(id) != $StorageNodeMaster->get(id))
                            $StorageNodeMaster->set(isMaster,0)->save();
                    }
                    unset($StorageNodeMaster);
                }
                // Hook
                $this->HookManager->processEvent(STORAGE_NODE_EDIT_SUCCESS,array(StorageNode=>&$StorageNode));
                // Log History event
                $this->FOGCore->logHistory(sprintf('%s: ID: %s, Name: %s', $this->foglang[SNUpdated],$StorageNode->get(id),$StorageNode->get(name)));
                // Set session message
                $this->FOGCore->setMessage($this->foglang[SNUpdated]);
                // Redirect back to self;
                $this->FOGCore->redirect($this->formAction);
            }
            else throw new Exception($this->foglang[DBupfailed]);
        } catch (Exception $e) {
            // Hook
            $this->HookManager->processEvent(STORAGE_NODE_EDIT_FAIL,array(StorageNode=>&$StorageNode));
            // Log History event
            $this->FOGCore->logHistory(sprintf('%s add failed: Name: %s, Error: %s',$this->foglang[SN],$_REQUEST[name],$e->getMessage()));
            // Set session message
            $this->FOGCore->setMessage($e->getMessage());
            // Redirect to new entry
            $this->FOGCore->redirect($this->formAction);
        }
    }
    public function delete_storage_node() {
        // Find
        $StorageNode = $this->obj;
        // Title
        $this->title = sprintf('%s: %s',$this->foglang[Remove],$StorageNode->get(name));
        // Headerdata
        unset($this->headerData);
        // Attributes
        $this->attributes = array(
            array(),
            array(),
        );
        // Templates
        $this->templates = array(
            '${field}',
            '${input}',
        );
        $fields = array(
            $this->foglang['ConfirmDel'].' <b>'.$StorageNode->get('name').'</b>' => '<input type="submit" value="${title}" />',
        );
        foreach((array)$fields AS $field => &$input) {
            $this->data[] = array(
                'field' => $field,
                'input' => $input,
                'title' => $this->title,
            );
        }
        unset($input);
        print '<form method="post" action="'.$this->formAction.'" class="c">';
        // Hook
        $this->HookManager->processEvent('STORAGE_NODE_DELETE', array('headerData' => &$this->headerData, 'data' => &$this->data, 'templates' => &$this->templates, 'attributes' => &$this->attributes));
        // Output
        $this->render();
        print '</form>';
    }
    public function delete_storage_node_post()
    {
        // Find
        $StorageNode = $this->obj;
        // Hook
        $this->HookManager->processEvent('STORAGE_NODE_DELETE_POST', array('StorageNode' => &$StorageNode));
        // POST
        try
        {
            // Destroy
            if (!$StorageNode->destroy())
                throw new Exception($this->foglang['FailDelSN']);
            // Hook
            $this->HookManager->processEvent('STORAGE_NODE_DELETE_SUCCESS', array('StorageNode' => &$StorageNode));
            // Log History event
            $this->FOGCore->logHistory(sprintf('%s: ID: %s, Name: %s', $this->foglang['SNDelSuccess'], $StorageNode->get('id'), $StorageNode->get('name')));
            // Set session message
            $this->FOGCore->setMessage(sprintf('%s: %s', $this->foglang['SNDelSuccess'], $StorageNode->get('name')));
            // Redirect
            $this->FOGCore->redirect(sprintf('?node=%s', $_REQUEST['node']));
        }
        catch (Exception $e)
        {
            // Hook
            $this->HookManager->processEvent('STORAGE_NODE_DELETE_FAIL', array('StorageNode' => &$StorageNode));
            // Log History event
            $this->FOGCore->logHistory(sprintf('%s %s: ID: %s, Name: %s', $this->foglang['SN'], $this->foglang['Deleted'], $StorageNode->get('id'), $StorageNode->get('name')));
            // Set session message
            $this->FOGCore->setMessage($e->getMessage());
            // Redirect
            $this->FOGCore->redirect($this->formAction);
        }
    }
    // STORAGE GROUP
    public function storage_group()
    {
        // Set title
        $this->title = $this->foglang['AllSG'];
        // Find data
        $StorageGroups = $this->getClass('StorageGroupManager')->find();
        // Row data
        foreach ((array)$StorageGroups AS $i => &$StorageGroup)
            $this->data[] = $StorageGroup->get();
        unset($StorageGroup);
        // Header row
        $this->headerData = array(
            $this->foglang['SG'],
            '',
        );
        // Row templates
        $this->templates = array(
            sprintf('<a href="?node=%s&sub=edit-storage-group&%s=${id}" title="%s">${name}</a>', $this->node, $this->id, $this->foglang['Edit']),
            sprintf('<a href="?node=%s&sub=edit-storage-group&%s=${id}" title="%s"><i class="icon fa fa-pencil"></i></a> <a href="?node=%s&sub=delete-storage-group&%s=${id}" title="%s"><i class="icon fa fa-minus-circle"></i></a>', $this->node, $this->id, $this->foglang['Edit'], $this->node, $this->id, $this->foglang['Delete'])
        );
        // Row attributes
        $this->attributes = array(
            array(),
            array('class' => 'c', 'width' => '50'),
        );
        // Hook
        $this->HookManager->processEvent('STORAGE_GROUP_DATA', array('headerData' => &$this->headerData, 'data' => &$this->data, 'templates' => &$this->templates, 'attributes' => &$this->attributes));
        // Output
        $this->render();
    }
    public function add_storage_group()
    {
        // Set title
        $this->title = $this->foglang['AddSG'];
        // Header Data
        unset($this->headerData);
        // Attributes
        $this->attributes = array(
            array(),
            array(),
        );
        // Templates
        $this->templates = array(
            '${field}',
            '${input}',
        );
        // Fields
        $fields = array(
            $this->foglang['SGName'] => '<input type="text" name="name" value="${storgrp_name}" />',
            $this->foglang['SGDesc'] => '<textarea name="description" rows="8" cols="40">${storgrp_desc}</textarea>',
            '&nbsp;' => '<input type="submit" value="'.$this->foglang['Add'].'" />',
        );
        print "\n\t\t\t".'<form method="post" action="'.$this->formAction.'">';
        foreach((array)$fields AS $field => &$input)
        {
            $this->data[] = array(
                'field' => $field,
                'input' => $input,
                'storgrp_name' => $_REQUEST['name'],
                'storgrp_desc' => $_REQUEST['description'],
            );
        }
        unset($input);
        // Hook
        $this->HookManager->processEvent('STORAGE_GROUP_ADD', array('headerData' => &$this->headerData, 'data' => &$this->data, 'templates' => &$this->templates, 'attributes' => &$this->attributes));
        // Output
        $this->render();
        print "</form>";
    }
    public function add_storage_group_post()
    {
        // Hook
        $this->HookManager->processEvent('STORAGE_GROUP_ADD_POST');
        // POST
        try
        {
            // Error checking
            if (empty($_REQUEST['name']))
                throw new Exception($this->foglang['SGNameReq']);
            if ($this->getClass('StorageGroupManager')->exists($_REQUEST['name']))
                throw new Exception($this->foglang['SGExist']);
            // Create new Object
            $StorageGroup = new StorageGroup(array(
                'name'		=> $_REQUEST['name'],
                'description'	=> $_REQUEST['description']
            ));
            // Save
            if ($StorageGroup->save())
            {
                // Hook
                $this->HookManager->processEvent('STORAGE_GROUP_ADD_POST_SUCCESS', array('StorageGroup' => &$StorageGroup));
                // Log History event
                $this->FOGCore->logHistory(sprintf('%s: ID: %s, Name: %s', $this->foglang['SGCreated'], $StorageGroup->get('id'), $StorageGroup->get('name')));
                // Set session message
                $this->FOGCore->setMessage($this->foglang['SGCreated']);
                // Redirect to new entry
                $this->FOGCore->redirect(sprintf('?node=%s&sub=edit-storage-group&%s=%s', $_REQUEST['node'], $this->id, $StorageGroup->get('id')));
            }
            else
                throw new Exception($this->foglang['DBupfailed']);
        }
        catch (Exception $e)
        {
            // Hook
            $this->HookManager->processEvent('STORAGE_GROUP_ADD_POST_FAIL', array('StorageGroup' => &$StorageGroup));
            // Log History event
            $this->FOGCore->logHistory(sprintf('%s add failed: Name: %s, Error: %s', $this->foglang['SG'], $_REQUEST['name'], $e->getMessage()));
            // Set session message
            $this->FOGCore->setMessage($e->getMessage());
            // Redirect to new entry
            $this->FOGCore->redirect($this->formAction);
        }
    }

    public function edit_storage_group()
    {
        // Find
        $StorageGroup = $this->obj;
        // Title
        $this->title = sprintf('%s: %s', $this->foglang['Edit'], $StorageGroup->get('name'));
        // Header Data
        unset($this->headerData);
        // Attributes
        $this->attributes = array(
            array(),
            array(),
        );
        // Templates
        $this->templates = array(
            '${field}',
            '${input}',
        );
        // Fields
        $fields = array(
            $this->foglang['SGName'] => '<input type="text" name="name" value="${storgrp_name}" />',
            $this->foglang['SGDesc'] => '<textarea name="description" rows="8" cols="40">${storgrp_desc}</textarea>',
            '&nbsp;' => '<input type="submit" value="'.$this->foglang['Update'].'" />',
        );
        print "\n\t\t\t".'<form method="post" action="'.$this->formAction.'">';
        foreach((array)$fields AS $field => &$input)
        {
            $this->data[] = array(
                'field' => $field,
                'input' => $input,
                'storgrp_name' => $StorageGroup->get('name'),
                'storgrp_desc' => $StorageGroup->get('description'),
            );
        }
        unset($input);
        // Hook
        $this->HookManager->processEvent('STORAGE_GROUP_EDIT', array('headerData' => &$this->headerData, 'data' => &$this->data, 'templates' => &$this->templates, 'attributes' => &$this->attributes));
        // Output
        $this->render();
        print "</form>";
    }
    public function edit_storage_group_post()
    {
        // Find
        $StorageGroup = $this->obj;
        // Hook
        $this->HookManager->processEvent('STORAGE_GROUP_EDIT_POST', array('StorageGroup' => &$StorageGroup));
        // POST
        try
        {
            // Error checking
            if (empty($_REQUEST['name']))
                throw new Exception($this->foglang['SGName']);
            if ($this->getClass('StorageGroupManager')->exists($_REQUEST['name'], $StorageGroup->get('id')))
                throw new Exception($this->foglang['SGExist']);
            // Update Object
            $StorageGroup	->set('name',		$_REQUEST['name'])
                ->set('description',	$_REQUEST['description']);
            // Save
            if ($StorageGroup->save())
            {
                // Hook
                $this->HookManager->processEvent('STORAGE_GROUP_EDIT_POST_SUCCESS', array('StorageGroup' => &$StorageGroup));
                // Log History event
                $this->FOGCore->logHistory(sprintf('%s: ID: %s, Name: %s', $this->foglang['SGUpdated'], $StorageGroup->get('id'), $StorageGroup->get('name')));
                // Set session message
                $this->FOGCore->setMessage($this->foglang['SGUpdated']);
                // Redirect to new entry
                $this->FOGCore->redirect(sprintf('?node=%s&sub=storage-group', $_REQUEST['node'], $this->id, $StorageGroup->get('id')));
            }
            else
                throw new Exception($this->foglang['DBupfailed']);
        }
        catch (Exception $e)
        {
            // Hook
            $this->HookManager->processEvent('STORAGE_GROUP_EDIT_FAIL', array('StorageGroup' => &$StorageGroup));
            // Log History event
            $this->FOGCore->logHistory(sprintf('%s add failed: Name: %s, Error: %s', $this->foglang['SG'], $_REQUEST['name'], $e->getMessage()));
            // Set session message
            $this->FOGCore->setMessage($e->getMessage());
            // Redirect to new entry
            $this->FOGCore->redirect($this->formAction);
        }
    }
    public function delete_storage_group()
    {
        // Find
        $StorageGroup = $this->obj;
        // Title
        $this->title = sprintf('%s: %s', $this->foglang['Remove'], $StorageGroup->get('name'));
        // Headerdata
        unset($this->headerData);
        // Attributes
        $this->attributes = array(
            array(),
            array(),
        );
        // Templates
        $this->templates = array(
            '${field}',
            '${input}',
        );
        $fields = array(
            $this->foglang['ConfirmDel'].' <b>'.$StorageGroup->get('name').'</b>' => '<input type="submit" value="${title}" />',
        );
        foreach((array)$fields AS $field => &$input)
        {
            $this->data[] = array(
                'field' => $field,
                'input' => $input,
                'title' => $this->title,
            );
        }
        unset($input);
        print "\n\t\t\t".'<form method="post" action="'.$this->formAction.'" class="c">';
        // Hook
        $this->HookManager->processEvent('STORAGE_GROUP_DELETE', array('headerData' => &$this->headerData, 'data' => &$this->data, 'templates' => &$this->templates, 'attributes' => &$this->attributes));
        // Output
        $this->render();
        print '</form>';
    }
    public function delete_storage_group_post()
    {
        // Find
        $StorageGroup = $this->obj;
        // Hook
        $this->HookManager->processEvent('STORAGE_GROUP_DELETE_POST', array('StorageGroup' => &$StorageGroup));
        // POST
        try
        {
            // Error checking
            if ($this->getClass('StorageGroupManager')->count() == 1)
                throw new Exception($this->foglang['OneSG']);
            // Destroy
            if (!$StorageGroup->destroy())
                throw new Exception($this->foglang['FailDelSG']);
            // Hook
            $this->HookManager->processEvent('STORAGE_GROUP_DELETE_POST_SUCCESS', array('StorageGroup' => &$StorageGroup));
            // Log History event
            $this->FOGCore->logHistory(sprintf('%s: ID: %s, Name: %s', $this->foglang['SGDelSuccess'], $StorageGroup->get('id'), $StorageGroup->get('name')));
            // Set session message
            $this->FOGCore->setMessage(sprintf('%s: %s', $this->foglang['SGDelSuccess'], $StorageGroup->get('name')));
            // Redirect
            $this->FOGCore->redirect(sprintf('?node=%s&sub=storage-group', $_REQUEST['node']));
        }
        catch (Exception $e)
        {
            // Hook
            $this->HookManager->processEvent('STORAGE_GROUP_DELETE_POST_FAIL', array('StorageGroup' => &$StorageGroup));
            // Log History event
            $this->FOGCore->logHistory(sprintf('%s %s: ID: %s, Name: %s', $this->foglang['SG'], $this->foglang['Deleted'], $StorageGroup->get('id'), $StorageGroup->get('name')));
            // Set session message
            $this->FOGCore->setMessage($e->getMessage());
            // Redirect
            $this->FOGCore->redirect($this->formAction);
        }
    }
}
