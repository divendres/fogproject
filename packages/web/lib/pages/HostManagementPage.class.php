<?php
class HostManagementPage extends FOGPage {
    public $node = 'host';
    public function __construct($name = '') {
        $this->name = 'Host Management';
        parent::__construct($this->name);
        if ($_SESSION[Pending-Hosts]) $this->menu[pending] = $this->foglang[PendingHosts];
        $this->menu[export] = $this->foglang[ExportHost];
        $this->menu[import] = $this->foglang[ImportHost];
        if ($_REQUEST[id]) {
            $this->obj = $this->getClass(Host,$_REQUEST[id]);
            if ($this->obj->isValid()) {
                $this->subMenu = array(
                    "$this->linkformat#host-general" => $this->foglang[General],
                );
                if (!$this->obj->get(pending)) $this->subMenu = array_merge($this->subMenu,array("$this->linkformat#host-tasks" => $this->foglang[BasicTasks]));
                $this->subMenu = array_merge($this->subMenu,array(
                    "$this->linkformat#host-active-directory" => $this->foglang[AD],
                    "$this->linkformat#host-printers" => $this->foglang[Printers],
                    "$this->linkformat#host-snapins" => $this->foglang[Snapins],
                    "$this->linkformat#host-service" => "{$this->foglang[Service]} {$this->foglang[Settings]}",
                    "$this->linkformat#host-hardware-inventory" => $this->foglang[Inventory],
                    "$this->linkformat#host-virus-history" => $this->foglang[VirusHistory],
                    "$this->linkformat#host-login-history" => $this->foglang[LoginHistory],
                    "$this->linkformat#host-image-history" => $this->foglang[ImageHistory],
                    "$this->linkformat#host-snapin-history" => $this->foglang[SnapinHistory],
                    $this->membership => $this->foglang[Membership],
                    $this->delformat => $this->foglang[Delete],
                ));
                $this->notes = array(
                    $this->foglang[Host]=>$this->obj->get(name),
                    $this->foglang[MAC]=>$this->obj->get(mac),
                    $this->foglang[Image]=>$this->obj->getImageName(),
                    $this->foglang[LastDeployed]=>$this->obj->get(deployed),
                );
                foreach ($this->obj->get(groups) AS $i => &$Group) {
                    $this->notes[$this->foglang[PrimaryGroup]] = $this->getClass(Group,$Group)->get(name);
                    break;
                }
                unset($Group);
            }
        }
        $this->HookManager->processEvent(SUB_MENULINK_DATA,array(menu=>&$this->menu,submenu=>&$this->subMenu,id=>&$this->id,notes=>&$this->notes));
        // Header row
        $this->headerData = array(
            '',
            '<input type="checkbox" name="toggle-checkbox" class="toggle-checkboxAction" />',
        );
        $_SESSION[FOGPingActive] ? array_push($this->headerData,'') : null;
        array_push($this->headerData,
            _('Host Name'),
            _('Deployed'),
            _('Task'),
            _('Edit/Remove'),
            _('Image')
        );
        // Row templates
        $this->templates = array(
            '<span class="icon fa fa-question hand" title="${host_desc}"></span>',
            '<input type="checkbox" name="host[]" value="${host_id}" class="toggle-action" />',
        );
        $_SESSION[FOGPingActive] ? array_push($this->templates,'<span class="icon ping"></span>') : null;
        array_push($this->templates,
            '<a href="?node=host&sub=edit&id=${host_id}" title="Edit: ${host_name}" id="host-${host_name}">${host_name}</a><br /><small>${host_mac}</small>',
            '<small>${deployed}</small>',
            '<a href="?node=host&sub=deploy&sub=deploy&type=1&id=${host_id}"><i class="icon fa fa-arrow-down" title="Download"></i></a> <a href="?node=host&sub=deploy&sub=deploy&type=2&id=${host_id}"><i class="icon fa fa-arrow-up" title="Upload"></i></a> <a href="?node=host&sub=deploy&type=8&id=${host_id}"><i class="icon fa fa-share-alt" title="Multi-cast"></i></a> <a href="?node=host&sub=edit&id=${host_id}#host-tasks"><i class="icon fa fa-arrows-alt" title="Deploy"></i></a>',
            '<a href="?node=host&sub=edit&id=${host_id}"><i class="icon fa fa-pencil" title="Edit"></i></a> <a href="?node=host&sub=delete&id=${host_id}"><i class="icon fa fa-minus-circle" title="Delete"></i></a>',
            '${image_name}'
        );
        // Row attributes
        $this->attributes = array(
            array(width=>22,id=>'host-${host_name}'),
            array('class'=>c,width=>16),
        );
        $_SESSION[FOGPingActive] ? array_push($this->attributes,array(width=>20)) : null;
        array_push($this->attributes,
            array(),
            array(width=>50,'class'=>c),
            array(width=>90,'class'=>r),
            array(width=>80,'class'=>c),
            array(width=>50,'class'=>r),
            array(width=>20,'class'=>r)
        );
    }
    /** @function index() the first page
     * @return void
     */
    public function index() {
        // Set title
        $this->title = $this->foglang[AllHosts];
        // Find data -> Push data
        if ($_SESSION[DataReturn] > 0 && $_SESSION[HostCount] > $_SESSION[DataReturn] && $_REQUEST[sub] != 'list') $this->FOGCore->redirect(sprintf('%s?node=%s&sub=search', $_SERVER[PHP_SELF], $this->node));
        $Hosts = $this->getClass(HostManager)->find(array(pending=>array('',0,null)));
        foreach ($Hosts AS $i => &$Host) {
            $this->data[] = array(
                host_id=>$Host->get(id),
                deployed=>$this->formatTime($Host->get(deployed)),
                host_name=>$Host->get(name),
                host_mac=>$Host->getMACAddress(),
                host_desc=>$Host->get(description),
                image_name=>$Host->getImageName(),
            );
        }
        // Hook
        $this->HookManager->processEvent(HOST_DATA,array(data=>&$this->data,templates=>&$this->templates,attributes=>&$this->attributes));
        $this->HookManager->processEvent(HOST_HEADER_DATA,array(headerData=>&$this->headerData,title=>&$this->title));
        // Output
        $this->render();
        unset($Host);
    }
    /** search_post()
        Provides the data from the search.
     */
    public function search_post() {
        // Find data -> Push data
        $Hosts = $this->getClass(HostManager)->search();
        foreach($Hosts AS $i => &$Host) {
            $this->data[] = array(
                host_id=>$Host->get(id),
                deployed=>$this->formatTime($Host->get(deployed)),
                host_name=>$Host->get(name),
                host_mac=>$Host->getMACAddress()->__toString(),
                host_desc=>$Host->get(description),
                image_name=>$Host->getImageName(),
            );
        }
        unset($Host);
        // Hook
        $this->HookManager->processEvent(HOST_DATA,array(data=>&$this->data,templates=>&$this->templates,attributes=>&$this->attributes));
        $this->HookManager->processEvent(HOST_HEADER_DATA,array(headerData=>&$this->headerData));
        // Output
        $this->render();
    }
    /** pending()
        Display's pending hosts from the host register.  This is where it will show hosts that are pending and can be approved en-mass.
     */
    public function pending() {
        $this->title = _('Pending Host List');
        print '<form method="post" action="'.$this->formAction.'">';
        $this->headerData = array(
            '',
            '<input type="checkbox" name="toggle-checkbox" class="toggle-checkboxAction" />',
            ($_SESSION[FOGPingActive] ? '' : null),
            _('Host Name'),
            _('Edit/Remove'),
        );
        // Row templates
        $this->templates = array(
            '<i class="icon fa fa-question hand" title="${host_desc}"></i>',
            '<input type="checkbox" name="host[]" value="${host_id}" class="toggle-host" />',
            ($_SESSION[FOGPingActive] ? '<span class="icon ping"></span>' : ''),
            '<a href="?node=host&sub=edit&id=${host_id}" title="Edit: ${host_name} Was last deployed: ${deployed}">${host_name}</a><br /><small>${host_mac}</small>',
            '<a href="?node=host&sub=edit&id=${host_id}"><i class="icon fa fa-pencil" title="Edit"></i></a> <a href="?node=host&sub=delete&id=${host_id}"><i class="icon fa fa-minus-circle" title="Delete"></i></a>',
        );
        // Row attributes
        $this->attributes = array(
            array(width=>22,id=>'host-${host_name}'),
            array('class' =>c,width=>16),
            ($_SESSION[FOGPingActive] ? array(width=>20) : ''),
            array(),
            array(width=>80,'class'=>c),
            array(width=>50,'class'=>r),
        );
        $Hosts = $this->getClass(HostManager)->find(array(pending=>1));
        foreach($Hosts AS $i => &$Host) {
            $this->data[] = array(
                host_id=>$Host->get(id),
                host_name=>$Host->get(name),
                host_mac=>$Host->getMACAddress(),
                host_desc=>$Host->get(description),
            );
        }
        unset($Host);
        // Hook
        $this->HookManager->processEvent(HOST_DATA,array(data=>&$this->data,templates=>&$this->templates,attributes=>&$this->attributes));
        $this->HookManager->processEvent(HOST_HEADER_DATA,array(headerData=>&$this->headerData));
        // Output
        $this->render();
        if (count($this->data) > 0) print '<center><input name="approvependhost" type="submit" value="'._('Approve selected Hosts').'"/>&nbsp;&nbsp;<input name="delpendhost" type="submit" value="'._('Delete selected Hosts').'"/></center>';
        print '</form>';
    }
    /** @function pending_post() approve/delete hosts
     * @return void
     */
    public function pending_post() {
        $countOfHosts = count($_REQUEST[host]);
        $count = 0;
        if (isset($_REQUEST[approvependhost])) {
            $Hosts = $this->getClass(HostManager)->find(array(id=>$_REQUEST[host]));
            foreach ($Hosts AS $i => &$Host) {
                $Host->set(pending,null);
                if ($Host->save()) $count++;
            }
            unset($HostID);
        }
        if (isset($_REQUEST[delpendhost])) {
            $this->getClass(HostManager)->destroy(array(id=>$_REQUEST[host]));
            $count = count($_REQUEST[host]);
        }
        $appdel = (isset($_REQUEST[approvependhost]) ? 'approved' : 'deleted');
        if ($count == $countOfHosts) {
            $this->FOGCore->setMessage(_("All hosts $appdel successfully"));
            $this->FOGCore->redirect('?node='.$_REQUEST[node]);
        }
        if ($count != $countOfHosts) {
            $this->FOGCore->setMessage($countApproved.' '._('of').' '.$countOfHosts.' '._("$appdel successfully"));
            $this->FOGCore->redirect($this->formAction);
        }
    }
    /** add()
        Add's a new host.
     */
    public function add() {
        // Set title
        $this->title = _('New Host');
        unset($this->data);
        // Header template
        $this->headerData = '';
        // Row templates
        $this->templates = array(
            '${field}',
            '${input}',
        );
        // Row attributes
        $this->attributes = array(
            array(),
            array(),
        );
        $fields = array(
            _('Host Name') => '<input type="text" name="host" value="${host_name}" maxlength="15" class="hostname-input" />*',
            _('Primary MAC') => '<input type="text" id="mac" name="mac" value="${host_mac}" />*<span id="priMaker"></span><span class="mac-manufactor"></span><i class="icon add-mac fa fa-plus-circle hand" title="'._('Add MAC').'"></i>',
            _('Host Description') => '<textarea name="description" rows="8" cols="40">${host_desc}</textarea>',
            _('Host Product Key') => '<input id="productKey" type="text" name="key" value="${host_key}" />',
            _('Host Image') => '${host_image}',
            _('Host Kernel') => '<input type="text" name="kern" value="${host_kern}" />',
            _('Host Kernel Arguments') => '<input type="text" name="args" value="${host_args}" />',
            _('Host Primary Disk') => '<input type="text" name="dev" value="${host_devs}" />',
        );
        $fieldsad = array(
            '<input style="display:none" type="text" name="fakeusernameremembered"/>' => '<input style="display:none" type="password" name="fakepasswordremembered"/>',
            _('Join Domain after image task') => '<input id="adEnabled" type="checkbox" name="domain" />',
            _('Domain Name') => '<input id="adDomain" class="smaller" type="text" name="domainname" value="${ad_name}" autocomplete="off" />',
            _('Domain OU') => '${ad_oufield}',
            _('Domain Username') => '<input id="adUsername" class="smaller" type="text" name="domainuser" value="${ad_user}" autocomplete="off" />',
            _('Domain Password').'<br/>'._('Must be encrypted') => '<input id="adPassword" class="smaller" type="password" name="domainpassword" value="${ad_pass}" autocomplete="off" />',
            '<input type="hidden" name="add" value="1" />' => '<input type="submit" value="'._('Add').'" />'
        );
        print '<h2>'._('Add new host definition').'</h2>';
        print '<form method="post" action="'.$this->formAction.'">';
        $this->HookManager->processEvent(HOST_FIELDS,array(fields=>&$fields));
        foreach ($fields AS $field => &$input) {
            $this->data[] = array(
                field=>$field,
                input=>$input,
                host_name=>$_REQUEST[host],
                host_mac=>$_REQUEST[mac],
                host_desc=>$_REQUEST[description],
                host_image=>$this->getClass(ImageManager)->buildSelectBox($_REQUEST[image],'','id'),
                host_kern=>$_REQUEST[kern],
                host_args=>$_REQUEST[args],
                host_devs=>$_REQUEST[dev],
                host_key=>$_REQUEST['key'],
            );
        }
        unset($input);
        // Hook
        $this->HookManager->processEvent(HOST_ADD_GEN,array(data=>&$this->data,templates=>&$this->templates,attributes=>&$this->attributes,fields=>&$fields));
        // Output
        $this->render();
        // unset for use later.
        unset ($this->data);
        print '<h2>'._('Active Directory').'</h2>';
        $OUs = explode('|',$this->FOGCore->getSetting(FOG_AD_DEFAULT_OU));
        foreach ((array)$OUs AS $i => &$OU) $OUOptions[] = $OU;
        unset($OU);
        $OUOptions = array_filter($OUOptions);
        if (count($OUOptions) > 1) {
            $OUs = array_unique((array)$OUOptions);
            $optionOU[] = '<option value=""> - '._('Please select an option').' - </option>';
            foreach ($OUs AS $i => &$OU) {
                $opt = preg_match('#;#i',$OU) ? preg_replace('#;#i','',$OU) : $OU;
                $optionOU[] = '<option value="'.$opt.'"'.($_REQUEST['ou'] == $opt ? ' selected="selected"' : (preg_match('#;#i',$OU) ? ' selected="selected"' : '')).'>'.$opt.'</option>';
            }
            unset($OU);
            $OUOptions = '<select id="adOU" class="smaller" name="ou">'.implode($optionOU).'</select>';
        } else $OUOptions = '<input id="adOU" class="smaller" type="text" name="ou" value="${ad_ou}" autocomplete="off" />';
        foreach ((array)$fieldsad AS $field => &$input) {
            $this->data[] = array(
                field=>$field,
                input=>$input,
                ad_dom=>(isset($_REQUEST[domain]) ? 'checked' : ''),
                ad_name=>$_REQUEST[domainname],
                ad_oufield=>$OUOptions,
                ad_user=>$_REQUEST[domainuser],
                ad_pass=>$_REQUEST[domainpassword],
                ad_ou=>$_REQUEST[ad_ou],
            );
        }
        unset($input);
        // Hook
        $this->HookManager->processEvent(HOST_ADD_AD,array(data=>&$this->data,templates=>&$this->templates,attributes=>&$this->attributes));
        // Output
        $this->render();
        print "</form>";
    }
    /** add_post()
        Actually add's the host.
     */
    public function add_post() {
        // Hook
        $this->HookManager->processEvent(HOST_ADD_POST);
        // POST ?
        try {
            $hostName = trim($_REQUEST[host]);
            // Error checking
            if (empty($hostName)) throw new Exception('Please enter a hostname');
            if (!$this->getClass(Host)->isHostnameSafe($hostName)) throw new Exception(_('Please enter a valid hostname'));
            if ($this->getClass(HostManager)->exists($hostName)) throw new Exception(_('Hostname Exists already'));
            if (empty($_REQUEST[mac])) throw new Exception(_('MAC Address is required'));
            $MAC = $this->getClass(MACAddress,$_REQUEST[mac]);
            if (!$MAC || !$MAC->isValid()) throw new Exception(_('MAC Format is invalid'));
            // Check if host exists with MAC Address.
            $Host = $this->getClass(HostManager)->getHostByMacAddresses($MAC);
            if ($Host && $Host->isValid()) throw new Exception(_('A host with this MAC already exists with Hostname: ').$Host->get(name));
            if ($this->getClass(HostManager)->exists($_REQUEST[host])) throw new Exception(_('Hostname already exists'));
            // Get all the service id's so they can be enabled.
            $ModuleIDs = $this->getClass(ModuleManager)->find('','','','','','','','id');
            $password = $_REQUEST[domainpassword];
            if ($_REQUEST[domainpassword]) $password = $this->encryptpw($_REQUEST[domainpassword]);
            $useAD = (int)isset($_REQUEST[domain]);
            $domain = trim($_REQUEST[domainname]);
            $ou = trim($_REQUEST[ou]);
            $user = trim($_REQUEST[domainuser]);
            $pass = trim($_REQUEST[domainpassword]);
            $passlegacy = trim($_REQUEST[domainpasswordlegacy]);
            // Define new Host object with data provided
            $Host = $this->getClass(Host)
                ->set(name,$hostName)
                ->set(description,$_REQUEST[description])
                ->set(imageID,$_REQUEST[image])
                ->set(kernel,$_REQUEST[kern])
                ->set(kernelArgs,$_REQUEST[args])
                ->set(kernelDevice,$_REQUEST[dev])
                ->set(productKey,base64_encode($_REQUEST['key']))
                ->addModule($ModuleIDs)
                ->addPriMAC($MAC)
                ->setAD($useAD,$domain,$ou,$user,$pass,true,true,$passlegacy);
            // Save to database
            if ($Host->save()) {
                // Hook
                $this->HookManager->processEvent(HOST_ADD_SUCCESS,array(Host=>&$Host));
                // Log History event
                $this->FOGCore->logHistory(sprintf('%s: ID: %s, Name: %s', _('Host added'), $Host->get(id), $Host->get(name)));
                // Set session message
                $this->FOGCore->setMessage(_('Host added'));
                // Redirect to new entry
                $this->FOGCore->redirect(sprintf('?node=%s&sub=edit&%s=%s', $this->REQUEST[node], $this->id, $Host->get(id)));
            } else throw new Exception('Database update failed');
        } catch (Exception $e) {
            // Hook
            $this->HookManager->processEvent(HOST_ADD_FAIL,array(Host=>&$Host));
            // Log History event
            $this->FOGCore->logHistory(sprintf('Host add failed: Name: %s, Error: %s',$_REQUEST[name],$e->getMessage()));
            // Set session message
            $this->FOGCore->setMessage($e->getMessage());
            // Redirect to new entry
            $this->FOGCore->redirect($this->formAction);
        }
    }
    /** edit()
        Edit host form information.
     */
    public function edit() {
        // Find
        $Inventory = $this->obj->get(inventory);
        // Get the associated Groups.
        // Title - set title for page title in window
        $this->title = sprintf('%s: %s', 'Edit', $this->obj->get(name));
        if ($_REQUEST[approveHost]) {
            $this->obj->set(pending,null);
            if ($this->obj->save()) $this->FOGCore->setMessage(_('Host approved'));
            else $this->FOGCore->setMessage(_('Host approval failed.'));
            $this->FOGCore->redirect('?node='.$_REQUEST[node].'&sub='.$_REQUEST[sub].'&id='.$_REQUEST[id]);
        }
        if ($this->obj->get(pending)) print '<h2><a href="'.$this->formAction.'&approveHost=1">'._('Approve this host?').'</a></h2>';
        unset($this->headerData);
        $this->attributes = array(
            array(),
            array(),
        );
        $this->templates = array(
            '${field}',
            '${input}',
        );
        if ($_REQUEST[confirmMAC]) {
            try {
                $this->obj->addPendtoAdd($_REQUEST[confirmMAC]);
                if ($this->obj->save()) $this->FOGCore->setMessage('MAC: '.$_REQUEST[confirmMAC].' Approved!');
            } catch (Exception $e) {
                $this->FOGCore->setMessage($e->getMessage());
            }
            $this->FOGCore->redirect('?node='.$_REQUEST[node].'&sub='.$_REQUEST[sub].'&id='.$_REQUEST[id]);
        }
        else if ($_REQUEST[approveAll]) {
            $this->obj->addPendtoAdd();
            if ($this->obj->save()) {
                $this->FOGCore->setMessage('All Pending MACs approved.');
                $this->FOGCore->redirect('?node='.$_REQUEST[node].'&sub='.$_REQUEST[sub].'&id='.$_REQUEST[id]);
            }
        }
        foreach($this->obj->get(additionalMACs) AS $i => &$MAC) {
            if ($MAC && $MAC->isValid())
                $addMACs .= '<div><input class="additionalMAC" type="text" name="additionalMACs[]" value="'.$MAC.'" /><input title="'._('Remove MAC').'" type="checkbox" onclick="this.form.submit()" class="delvid" id="rm'.$MAC.'" name="additionalMACsRM[]" value="'.$MAC.'" /><label for="rm'.$MAC.'" class="icon fa fa-minus-circle hand">&nbsp;</label><span class="icon icon-hand" title="'._('Make Primary').'"><input type="radio" name="primaryMAC" value="'.$MAC.'" /></span><span class="icon icon-hand" title="'._('Ignore MAC on Client').'"><input type="checkbox" name="igclient[]" value="'.$MAC.'" '.$this->obj->clientMacCheck($MAC).' /></span><span class="icon icon-hand" title="'._('Ignore MAC for imaging').'"><input type="checkbox" name="igimage[]" value="'.$MAC.'" '.$this->obj->imageMacCheck($MAC).'/></span><br/><span class="mac-manufactor"></span></div>';
        }
        unset($MAC);
        foreach ($this->obj->get(pendingMACs) AS $i => &$MAC) $pending .= '<div><input class="pending-mac" type="text" name="pendingMACs[]" value="'.$MAC.'" /><a href="'.$this->formAction.'&confirmMAC='.$MAC.'"><i class="icon fa fa-check-circle"></i></a><span class="mac-manufactor"></span></div>';
        unset($MAC);
        if ($pending != null && $pending != '')
            $pending .= '<div>'._('Approve All MACs?').'<a href="'.$this->formAction.'&approveAll=1"><i class="icon fa fa-check-circle"></i></a></div>';
        $imageSelect = $this->getClass('ImageManager')->buildSelectBox($this->obj->get(imageID));
        $fields = array(
            _('Host Name') => '<input type="text" name="host" value="'.$this->obj->get(name).'" maxlength="15" class="hostname-input" />*',
            _('Primary MAC') => '<input type="text" name="mac" id="mac" value="'.$this->obj->get(mac).'" />*<span id="priMaker"></span><i class="icon add-mac fa fa-plus-circle hand" title="'._('Add MAC').'"></i>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="icon icon-hand" title="'._('Ignore MAC on Client').'"><input type="checkbox" name="igclient[]" value="'.$this->obj->get(mac).'" '.$this->obj->clientMacCheck().' /></span><span class="icon icon-hand" title="'._('Ignore MAC for imaging').'"><input type="checkbox" name="igimage[]" value="'.$this->obj->get(mac).'" '.$this->obj->imageMacCheck().'/></span><br/><span class="mac-manufactor"></span>',
            '<div id="additionalMACsRow">'._('Additional MACs').'</div>' => '<div id="additionalMACsCell">'.$addMACs.'</div>',
            ($this->obj->get('pendingMACs') ? _('Pending MACs') : null) => ($this->obj->get('pendingMACs') ? $pending : null),
            _('Host Description') => '<textarea name="description" rows="8" cols="40">'.$this->obj->get(description).'</textarea>',
            _('Host Product Key') => '<input id="productKey" type="text" name="key" value="'.base64_decode($this->obj->get('key')).'" />',
            _('Host Image') => $imageSelect,
            _('Host Kernel') => '<input type="text" name="kern" value="'.$this->obj->get(kernel).'" />',
            _('Host Kernel Arguments') => '<input type="text" name="args" value="'.$this->obj->get(kernelArgs).'" />',
            _('Host Primary Disk') => '<input type="text" name="dev" value="'.$this->obj->get(kernelDevice).'" />',
            '&nbsp' => '<input type="submit" value="'._('Update').'" />',
        );
        $this->HookManager->processEvent('HOST_FIELDS', array('fields' => &$fields,'Host' => &$this->obj));
        print '<div id="tab-container">';
        print "<!-- General -->";
        print '<div id="host-general">';
        if ($this->obj->get(pub_key) || $this->obj->get(sec_tok)) $this->form = '<center><div id="resetSecDataBox"></div><input type="button" id="resetSecData" /></center><br/>';
        print '<form method="post" action="'.$this->formAction.'&tab=host-general">';
        print '<h2>'._('Edit host definition').'</h2>';
        foreach($fields AS $field => &$input) {
            $this->data[] = array(
                'field' => $field,
                'input' => $input,
            );
        }
        unset($input);
        // Hook
        $this->HookManager->processEvent('HOST_EDIT_GEN', array('headerData' => &$this->headerData, 'data' => &$this->data, 'templates' => &$this->templates, 'attributes' => &$this->attributes, 'Host' => &$this->obj));
        $this->render();
        print '</form></div>';
        unset($this->data,$this->form);
        unset($this->data,$this->headerData,$this->attributes);
        if (!$this->obj->get('pending')) $this->basictasksOptions();
        $this->adFieldsToDisplay();
        print "<!-- Printers -->";
        print '<div id="host-printers" class="organic-tabs-hidden">';
        print '<form method="post" action="'.$this->formAction.'&tab=host-printers">';
        // Create Header for non associated printers
        $this->headerData = array(
            '<input type="checkbox" name="toggle-checkboxprint" class="toggle-checkboxprint" />',
            _('Printer Name'),
            _('Configuration'),
        );
        // Template for these printers:
        $this->templates = array(
            '<input type="checkbox" name="printer[]" value="${printer_id}" class="toggle-print" />',
            '<a href="?node=printer&sub=edit&id=${printer_id}">${printer_name}</a>',
            '${printer_type}',
        );
        $this->attributes = array(
            array('width' => 16, 'class' => 'c'),
            array('width' => 50, 'class' => 'l'),
            array('width' => 50, 'class' => 'r'),
        );
        $Printers = $this->getClass(PrinterManager)->find(array(id=>$this->obj->get(printersnotinme)));
        foreach ($Printers AS $i => &$Printer) {
            $this->data[] = array(
                printer_id => $Printer->get(id),
                printer_name => addslashes($Printer->get(name)),
                printer_type => $Printer->get(config),
            );
        }
        unset($Printer);
        $PrintersFound = false;
        if (count($this->data) > 0) {
            $PrintersFound = true;
            print '<center><label for="hostPrinterShow">'._('Check here to see what printers can be added').'&nbsp;&nbsp;<input type="checkbox" name="hostPrinterShow" id="hostPrinterShow" /></label></center>';
            print '<div id="printerNotInHost">';
            print '<h2>'._('Add new printer(s) to this host').'</h2>';
            $this->HookManager->processEvent('HOST_ADD_PRINTER', array('headerData' => &$this->headerData,'data' => &$this->data,'templates' => &$this->templates,'attributes' => &$this->attributes));
            // Output
            $this->render();
            print "</div>";
        }
        unset($this->data);
        $this->headerData = array(
            '<input type="checkbox" name="toggle-checkbox" class="toggle-checkboxAction" />',
            _('Default'),
            _('Printer Alias'),
            _('Printer Type'),
        );
        $this->attributes = array(
            array('class' => 'c','width' => 16),
            array(),
            array(),
            array(),
        );
        $this->templates = array(
            '<input type="checkbox" name="printerRemove[]" value="${printer_id}" class="toggle-action" />',
            '<input class="default" type="radio" name="default" id="printer${printer_id}" value="${printer_id}"${is_default} /><label for="printer${printer_id}" class="icon icon-hand" title="'._('Default Printer Select').'">&nbsp;</label><input type="hidden" name="printerid[]" value="${printer_id}" />',
            '<a href="?node=printer&sub=edit&id=${printer_id}">${printer_name}</a>',
            '${printer_type}',
        );
        print "<h2>"._('Host Printer Configuration').'</h2>';
        print "<p>"._('Select Management Level for this Host').'</p>';
        print "<p>";
        print '<span class="icon fa fa-question hand" title="'._('This setting turns off all FOG Printer Management.  Although there are multiple levels already between host and global settings, this is just another to ensure safety').'"></span><input type="radio" name="level" value="0"'.($this->obj->get(printerLevel) == 0 ? 'checked' : '').' />'._('No Printer Management').'<br/>';
        print '<span class="icon fa fa-question hand" title="'._('This setting only adds and removes printers that are managed by FOG.  If the printer exists in printer management but is not assigned to a host, it will remove the printer if it exists on the unsigned host.  It will add printers to the host that are assigned.').'"></span><input type="radio" name="level" value="1"'.($this->obj->get(printerLevel) == 1 ? 'checked' : '').' />'._('FOG Managed Printers').'<br/>';
        print '<span class="icon fa fa-question hand" title="'._('This setting will only allow FOG Assigned printers to be added to the host.  Any printer that is assigned will be removed including non-FOG managed printers.').'"></span><input type="radio" name="level" value="2"'.($this->obj->get(printerLevel) == 2 ? 'checked' : '').' />'._('Add and Remove').'<br/></p>';
        $Printers = $this->getClass(PrinterManager)->find(array(id=>$this->obj->get(printers)));
        foreach ($Printers AS $i => &$Printer) {
            $this->data[] = array(
                'printer_id' => $Printer->get(id),
                'is_default' => ($this->obj->getDefault($Printer->get(id)) ? 'checked' : ''),
                'printer_name' => addslashes($Printer->get(name)),
                'printer_type' => $Printer->get(config),
            );
        }
        unset($Printer);
        // Hook
        $this->HookManager->processEvent('HOST_EDIT_PRINTER', array('headerData' => &$this->headerData, 'data' => &$this->data, 'templates' => &$this->templates, 'attributes' => &$this->attributes));
        // Output
        $this->render();
        if ($PrintersFound || count($this->data) > 0) print '<center><input type="submit" value="'._('Update').'" name="updateprinters"/>';
        if (count($this->data) > 0) print '&nbsp;&nbsp;<input type="submit" value="'._('Remove selected printers').'" name="printdel"/>';
        print "</center>";
        // Reset for next tab
        unset($this->data, $this->headerData);
        print '</form></div><!-- Snapins --><div id="host-snapins" class="organic-tabs-hidden">';
        print '<h2>'._('Snapins').'</h2>';
        print '<form method="post" action="'.$this->formAction.'&tab=host-snapins">';
        // Create the header:
        $this->headerData = array(
            '<input type="checkbox" name="toggle-checkboxsnapin" class="toggle-checkboxsnapin" />',
            _('Snapin Name'),
            _('Created'),
        );
        // Create the template:
        $this->templates = array(
            '<input type="checkbox" name="snapin[]" value="${snapin_id}" class="toggle-snapin" />',
            sprintf('<a href="?node=%s&sub=edit&id=${snapin_id}" title="%s">${snapin_name}</a>','snapin',_('Edit')),
            '${snapin_created}',
        );
        // Create the attributes:
        $this->attributes = array(
            array('width' => 16, 'class' => 'c'),
            array('width' => 90, 'class' => 'l'),
            array('width' => 20, 'class' => 'r'),
        );
        $Snapins = $this->getClass(SnapinManager)->find(array(id=>$this->obj->get(snapinsnotinme)));
        foreach($Snapins AS $i => &$Snapin) {
            $this->data[] = array(
                snapin_id => $Snapin->get(id),
                snapin_name => $Snapin->get(name),
                snapin_created => $Snapin->get(createdTime),
            );
        }
        unset($Snapin);
        if (count($this->data) > 0) {
            print '<center><label for="hostSnapinShow">'._('Check here to see what snapins can be added').'&nbsp;&nbsp;<input type="checkbox" name="hostSnapinShow" id="hostSnapinShow" /></label>';
            print '<div id="snapinNotInHost">';
            $this->HookManager->processEvent('HOST_SNAPIN_JOIN',array('headerData' => &$this->headerData,'data' => &$this->data,'templates' => &$this->templates,'attributes' => &$this->attributes));
            $this->render();
            print '<input type="submit" value="'._('Add Snapin(s)').'" /></form></div></center>';
            print '<form method="post" action="'.$this->formAction.'&tab=host-snapins">';
            unset($this->data);
        }
        $this->headerData = array(
            '<input type="checkbox" name="toggle-checkbox" class="toggle-checkboxAction" />',
            _('Snapin Name'),
        );
        $this->attributes = array(
            array('class' => 'c','width' => 16),
            array(),
        );
        $this->templates = array(
            '<input type="checkbox" name="snapinRemove[]" value="${snap_id}" class="toggle-action" />',
            '<a href="?node=snapin&sub=edit&id=${snap_id}">${snap_name}</a>',
        );
        $Snapins = $this->getClass(SnapinManager)->find(array(id=>$this->obj->get(snapins)));
        foreach ($Snapins AS $i => &$Snapin) {
            $this->data[] = array(
                snap_id => $Snapin->get(id),
                snap_name => $Snapin->get(name),
            );
        }
        unset($Snapin);
        // Hook
        $this->HookManager->processEvent(HOST_EDIT_SNAPIN,array(headerData=>&$this->headerData,data=>&$this->data,templates=>&$this->templates,attributes=>&$this->attributes));
        // Output
        $this->render();
        print '<center><input type="submit" name="snaprem" value="'._('Remove selected snapins').'"/></center></form></div>';
        // Reset for next tab
        unset($this->data, $this->headerData);
        print '<!-- Service Configuration -->';
        $this->attributes = array(
            array('width' => 270),
            array('class' => 'c'),
            array('class' => 'r'),
        );
        $this->templates = array(
            '${mod_name}',
            '${input}',
            '${span}',
        );
        $this->data[] = array(
            'mod_name' => 'Select/Deselect All',
            'input' => '<input type="checkbox" class="checkboxes" id="checkAll" name="checkAll" value="checkAll" />',
            'span' => ''
        );
        print '<div id="host-service" class="organic-tabs-hidden">';
        print '<form method="post" action="'.$this->formAction.'&tab=host-service">';
        print '<h2>'._('Service Configuration').'</h2><fieldset>';
        print "<legend>"._('General').'</legend>';
        $ModOns = $this->getClass('ModuleAssociationManager')->find(array('hostID' => $this->obj->get(id)),'','','','','','','moduleID');
        $moduleName = $this->getGlobalModuleStatus();
        $Modules = $this->getClass(ModuleManager)->find();
        foreach ($Modules AS $i => &$Module) {
            $this->data[] = array(
                'input' => '<input type="checkbox" '.($moduleName[$Module->get(shortName)] || ($moduleName[$Module->get(shortName)] && $Module->get(isDefault)) ? 'class="checkboxes"' : '').' name="modules[]" value="${mod_id}" ${checked} '.(!$moduleName[$Module->get(shortName)] ? 'disabled' : '').' />',
                'span' => '<span class="icon fa fa-question fa-1x hand" title="${mod_desc}"></span>',
                'checked' => (in_array($Module->get(id),$ModOns) ? 'checked' : ''),
                'mod_name' => $Module->get(name),
                'mod_shname' => $Module->get(shortName),
                'mod_id' => $Module->get(id),
                'mod_desc' => str_replace('"','\"',$Module->get(description)),
            );
        }
        unset($ModOns,$Module);
        $this->data[] = array(
            'mod_name' => '&nbsp',
            'input' => '',
            'span' => '<input type="submit" name="updatestatus" value="'._('Update').'" />',
        );
        // Hook
        $this->HookManager->processEvent(HOST_EDIT_SERVICE,array(headerData=>&$this->headerData,data=>&$this->data,templates=>&$this->templates,attributes=>&$this->attributes));
        // Output
        $this->render();
        // Reset for next tab
        unset($this->data);
        print '</fieldset></form>';
        print '<form method="post" action="'.$this->formAction.'&tab=host-service">';
        print "<fieldset>";
        print "<legend>"._('Host Screen Resolution').'</legend>';
        $this->attributes = array(
            array('class' => 'l','style' => 'padding-right: 25px'),
            array('class' => 'c'),
            array('class' => 'r'),
        );
        $this->templates = array(
            '${field}',
            '${input}',
            '${span}',
        );
        $Services = $this->getClass(ServiceManager)->find(array(name=>array('FOG_SERVICE_DISPLAYMANAGER_X','FOG_SERVICE_DISPLAYMANAGER_Y','FOG_SERVICE_DISPLAYMANAGER_R')),'OR','id');
        foreach($Services AS $i => &$Service) {
            $this->data[] = array(
                'input' => '<input type="text" name="${type}" value="${disp}" />',
                'span' => '<span class="icon fa fa-question fa-1x hand" title="${desc}"></span>',
                'field' => ($Service->get('name') == 'FOG_SERVICE_DISPLAYMANAGER_X' ? _('Screen Width (in pixels)') : ($Service->get('name') == 'FOG_SERVICE_DISPLAY_MANAGER_Y' ? _('Screen Height (in pixels)') : ($Service->get('name') == 'FOG_SERVICE_DISPLAYMANAGER_R' ? _('Screen Refresh Rate (in Hz)') : ''))),
                'type' => ($Service->get('name') == 'FOG_SERVICE_DISPLAYMANAGER_X' ? 'x' : ($Service->get('name') == 'FOG_SERVICE_DISPLAYMANAGER_Y' ? 'y' : ($Service->get('name') == 'FOG_SERVICE_DISPLAYMANAGER_R' ? 'r' : ''))),
                'disp' => ($Service->get('name') == 'FOG_SERVICE_DISPLAYMANAGER_X' ? $this->obj->getDispVals('width') : ($Service->get('name') == 'FOG_SERVICE_DISPLAYMANAGER_Y' ? $this->obj->getDispVals('height') : ($Service->get('name') == 'FOG_SERVICE_DISPLAYMANAGER_R' ? $this->obj->getDispVals('refresh') : ''))),
                'desc' => $Service->get('description'),
            );
        }
        unset($Service);
        $this->data[] = array(
            'field' => '',
            'input' => '',
            'span' => '<input type="submit" name="updatedisplay" value="'._('Update').'" />',
        );
        // Hook
        $this->HookManager->processEvent(HOST_EDIT_DISPSERV,array(headerData=>&$this->headerData,data=>&$this->data,templates=>&$this->templates,attributes=>&$this->attributes));
        // Output
        $this->render();
        // Reset for next tab
        unset($this->data);
        print '</fieldset></form>';
        print '<form method="post" action="'.$this->formAction.'&tab=host-service">';
        print '<fieldset>';
        print "<legend>"._('Auto Log Out Settings').'</legend>';
        $this->attributes = array(
            array('width' => 270),
            array('class' => 'c'),
            array('class' => 'r'),
        );
        $this->templates = array(
            '${field}',
            '${input}',
            '${desc}',
        );
        $Service = current($this->getClass(ServiceManager)->find(array(name=>'FOG_SERVICE_AUTOLOGOFF_MIN')));
        $this->data[] = array(
            'field' => _('Auto Log Out Time (in minutes)'),
            'input' => '<input type="text" name="tme" value="${value}" />',
            'desc' => '<span class="icon fa fa-question fa-1x hand" title="${serv_desc}"></span>',
            'value' => $this->obj->getAlo() ? $this->obj->getAlo() : $Service->get('value'),
            'serv_desc' => $Service->get(description),
        );
        $this->data[] = array(
            'field' => '',
            'input' => '',
            'desc' => '<input type="submit" name="updatealo" value="'._('Update').'" />',
        );
        // Hook
        $this->HookManager->processEvent(HOST_EDIT_ALO,array(headerData=>&$this->headerData,data=>&$this->data,templates=>&$this->templates,attributes=>&$this->attributes));
        // Output
        $this->render();
        // Reset for next tab
        unset($this->data,$fields);
        print "</fieldset>";
        print "</form>";
        print "</div>";
        print "<!-- Inventory -->";
        $this->attributes = array(
            array(),
            array(),
        );
        $this->templates = array(
            '${field}',
            '${input}',
        );
        $fields = array(
            _('Primary User') => '<input type="text" value="${inv_user}" name="pu" />',
            _('Other Tag #1') => '<input type="text" value="${inv_oth1}" name="other1" />',
            _('Other Tag #2') => '<input type="text" value="${inv_oth2}" name="other2" />',
            _('System Manufacturer') => '${inv_sysman}',
            _('System Product') => '${inv_sysprod}',
            _('System Version') => '${inv_sysver}',
            _('System Serial Number') => '${inv_sysser}',
            _('System Type') => '${inv_systype}',
            _('BIOS Vendor') => '${bios_ven}',
            _('BIOS Version') => '${bios_ver}',
            _('BIOS Date') => '${bios_date}',
            _('Motherboard Manufacturer') => '${mb_man}',
            _('Motherboard Product Name') => '${mb_name}',
            _('Motherboard Version') => '${mb_ver}',
            _('Motherboard Serial Number') => '${mb_ser}',
            _('Motherboard Asset Tag') => '${mb_asset}',
            _('CPU Manufacturer') => '${cpu_man}',
            _('CPU Version') => '${cpu_ver}',
            _('CPU Normal Speed') => '${cpu_nspeed}',
            _('CPU Max Speed') => '${cpu_mspeed}',
            _('Memory') => '${inv_mem}',
            _('Hard Disk Model') => '${hd_model}',
            _('Hard Disk Firmware') => '${hd_firm}',
            _('Hard Disk Serial Number') => '${hd_ser}',
            _('Chassis Manufacturer') => '${case_man}',
            _('Chassis Version') => '${case_ver}',
            _('Chassis Serial') => '${case_ser}',
            _('Chassis Asset') => '${case_asset}',
            '<input type="hidden" name="update" value="1" />' => '<input type="submit" value="'._('Update').'" />',
        );
        print '<div id="host-hardware-inventory" class="organic-tabs-hidden">';
        print '<form method="post" action="'.$this->formAction.'&tab=host-hardware-inventory">';
        print '<h2>'._('Host Hardware Inventory').'</h2>';
        if ($Inventory && $Inventory->isValid()) {
            foreach(array('cpuman','cpuversion') AS &$x) $Inventory->set($x,implode(' ',array_unique(explode(' ',$Inventory->get($x)))));
            unset($x);
            foreach((array)$fields AS $field => &$input) {
                $this->data[] = array(
                    'field' => $field,
                    'input' => $input,
                    'inv_user' => $Inventory->get('primaryUser'),
                    'inv_oth1' => $Inventory->get('other1'),
                    'inv_oth2' => $Inventory->get('other2'),
                    'inv_sysman' => $Inventory->get('sysman'),
                    'inv_sysprod' => $Inventory->get('sysproduct'),
                    'inv_sysver' => $Inventory->get('sysversion'),
                    'inv_sysser' => $Inventory->get('sysserial'),
                    'inv_systype' => $Inventory->get('systype'),
                    'bios_ven' => $Inventory->get('biosvendor'),
                    'bios_ver' => $Inventory->get('biosversion'),
                    'bios_date' => $Inventory->get('biosdate'),
                    'mb_man' => $Inventory->get('mbman'),
                    'mb_name' => $Inventory->get('mbproductname'),
                    'mb_ver' => $Inventory->get('mbversion'),
                    'mb_ser' => $Inventory->get('mbserial'),
                    'mb_asset' => $Inventory->get('mbasset'),
                    'cpu_man' => $Inventory->get('cpuman'),
                    'cpu_ver' => $Inventory->get('cpuversion'),
                    'cpu_nspeed' => $Inventory->get('cpucurrent'),
                    'cpu_mspeed' => $Inventory->get('cpumax'),
                    'inv_mem' => $Inventory->getMem(),
                    'hd_model' => $Inventory->get('hdmodel'),
                    'hd_firm' => $Inventory->get('hdfirmware'),
                    'hd_ser' => $Inventory->get('hdserial'),
                    'case_man' => $Inventory->get('caseman'),
                    'case_ver' => $Inventory->get('caseversion'),
                    'case_ser' => $Inventory->get('caseserial'),
                    'case_asset' => $Inventory->get('caseasset'),
                );
            }
            unset($input);
        }
        else unset($this->data);
        // Hook
        $this->HookManager->processEvent('HOST_INVENTORY', array('headerData' => &$this->headerData, 'data' => &$this->data, 'templates' => &$this->templates, 'attributes' => &$this->attributes));
        // Output
        $this->render();
        // Reset for next tab
        unset($this->data,$fields);
        print "</form>";
        print "\n\t\t\t</div>";
        print "\n\t\t\t<!-- Virus -->";
        $this->headerData = array(
            _('Virus Name'),
            _('File'),
            _('Mode'),
            _('Date'),
            _('Clear'),
        );
        $this->attributes = array(
            array(),
            array(),
            array(),
            array(),
            array(),
        );
        $this->templates = array(
            '<a href="http://www.google.com/search?q=${virus_name}" target="_blank">${virus_name}</a>',
            '${virus_file}',
            '${virus_mode}',
            '${virus_date}',
            '<input type="checkbox" id="vir_del${virus_id}" class="delvid" name="delvid" onclick="this.form.submit()" value="${virus_id}" /><label for="${virus_id}" class="icon icon-hand" title="'._('Delete').' ${virus_name}"><i class="icon fa fa-minus-circle link"></i>&nbsp;</label>',
        );
        print "\n\t\t\t".'<div id="host-virus-history" class="organic-tabs-hidden">';
        print "\n\t\t\t".'<form method="post" action="'.$this->formAction.'&tab=host-virus-history">';
        print "\n\t\t\t".'<h2>'._('Virus History').'</h2>';
        print "\n\t\t\t".'<h2><a href="#"><input type="checkbox" class="delvid" id="all" name="delvid" value="all" onclick="this.form.submit()" /><label for="all">('._('clear all history').')</label></a></h2>';
        $MACs = $this->obj->getMyMacs();
        $Viruses = $this->getClass(VirusManager)->find(array('hostMAC' => $MACs));
        unset($MACs);
        foreach($Viruses AS $i => &$Virus) {
            $this->data[] = array(
                'virus_name' => $Virus->get('name'),
                'virus_file' => $Virus->get('file'),
                'virus_mode' => ($Virus->get('mode') == 'q' ? _('Quarantine') : ($Virus->get('mode') == 's' ? _('Report') : 'N/A')),
                'virus_date' => $Virus->get('date'),
                'virus_id' => $Virus->get('id'),
            );
        }
        unset($Virus);
        // Hook
        $this->HookManager->processEvent('HOST_VIRUS', array('headerData' => &$this->headerData, 'data' => &$this->data, 'templates' => &$this->templates, 'attributes' => &$this->attributes));
        // Output
        $this->render();
        // Reset for next tab
        unset($this->data,$this->headerData);
        print "</form>";
        print "\n\t\t\t</div>";
        print "\n\t\t\t<!-- Login History -->";
        print "\n\t\t\t".'<div id="host-login-history" class="organic-tabs-hidden">';
        print "\n\t\t\t<h2>"._('Host Login History').'</h2>';
        print "\n\t\t\t".'<form id="dte" method="post" action="'.$this->formAction.'&tab=host-login-history">';
        $this->headerData = array(
            _('Time'),
            _('Action'),
            _('Username'),
            _('Description')
        );
        $this->attributes = array(
            array(),
            array(),
            array(),
            array(),
        );
        $this->templates = array(
            '${user_time}',
            '${action}',
            '${user_name}',
            '${user_desc}',
        );
        $Dates = $this->getClass(UserTrackingManager)->find(array('id' => $this->obj->get(users)),'','','','','','','date');
        $Dates = array_unique((array)$Dates);
        if ($Dates) {
            rsort($Dates);
            print "\n\t\t\t<p>"._('View History for').'</p>';
            foreach((array)$Dates AS $i => &$Date) {
                if ($_REQUEST['dte'] == '') $_REQUEST['dte'] = $Date;
                $optionDate[] = '<option value="'.$Date.'" '.($Date == $_REQUEST['dte'] ? 'selected="selected"' : '').'>'.$Date.'</option>';
            }
            unset($Date);
            print "\n\t\t\t".'<select name="dte" id="loghist-date" size="1" onchange="document.getElementById(\'dte\').submit()">'.implode($optionDate).'</select>';
            print "\n\t\t\t".'<a href="#" onclick="document.getElementByID(\'dte\').submit()"><i class="icon fa fa-play noBorder"></i></a></p>';
            $UserTracking = $this->getClass(UserTrackingManager)->find(array(id=>$this->obj->get(users)));
            foreach ($UserTracking AS $i => &$UserLogin) {
                if ($UserLogin->get('date') == $_REQUEST['dte']) {
                    $this->data[] = array(
                        'action' => ($UserLogin->get('action') == 1 ? _('Login') : ($UserLogin->get('action') == 0 ? _('Logout') : '')),
                        'user_name' => $UserLogin->get('username'),
                        'user_time' => $UserLogin->get('datetime'),
                        'user_desc' => $UserLogin->get('description'),
                    );
                }
            }
            unset($UserLogin);
            // Hook
            $this->HookManager->processEvent('HOST_USER_LOGIN', array('headerData' => &$this->headerData, 'data' => &$this->data, 'templates' => &$this->templates, 'attributes' => &$this->attributes));
            // Output
            $this->render();
        }
        else print "<p>"._('No user history data found!').'</p>';
        // Reset for next tab
        unset($this->data,$this->headerData);
        print '<div id="login-history" style="width:575px;height:200px;" /></div>';
        print "</form>";
        print "</div>";
        print "".'<div id="host-image-history" class="organic-tabs-hidden">';
        print "<h2>"._('Host Imaging History').'</h2>';
        // Header Data for host image history
        $this->headerData = array(
            _('Image Name'),
            _('Imaging Type'),
            '<small>'._('Start - End').'</small><br />'._('Duration'),
        );
        // Templates for the host image history
        $this->templates = array(
            '${image_name}',
            '${image_type}',
            '<small>${start_time} - ${end_time}</small><br />${duration}',
        );
        // Attributes
        $this->attributes = array(
            array(),
            array(),
            array(),
        );
        $ImagingLogs = $this->getClass(ImagingLogManager)->find(array('hostID' => $this->obj->get(id)));
        foreach ($ImagingLogs AS $i => &$ImageLog) {
            $Start = $ImageLog->get('start');
            $End = $ImageLog->get('finish');
            $this->data[] = array(
                'start_time' => $this->formatTime($Start),
                'end_time' => $this->formatTime($End),
                'duration' => $this->diff($Start,$End),
                'image_name' => $ImageLog->get('image'),
                'image_type' => $ImageLog->get('type'),
            );
        }
        unset($ImageLog);
        // Hook
        $this->HookManager->processEvent('HOST_IMAGE_HIST', array('headerData' => &$this->headerData, 'data' => &$this->data, 'templates' => &$this->templates, 'attributes' => &$this->attributes));
        // Output
        $this->render();
        unset($this->data);
        print "\n\t\t\t".'</div>';
        print "\n\t\t\t".'<div id="host-snapin-history">';
        $this->headerData = array(
            _('Snapin Name'),
            _('Start Time'),
            _('Complete'),
            _('Duration'),
            _('Return Code'),
        );
        $this->templates = array(
            '${snapin_name}',
            '${snapin_start}',
            '${snapin_end}',
            '${snapin_duration}',
            '${snapin_return}',
        );
        $SnapinJobIDs = $this->getClass(SnapinJobManager)->find(array('hostID' => $this->obj->get(id)),'','','','','','','id');
        $SnapinTasks = $this->getClass(SnapinTaskManager)->find(array('jobID' => $SnapinJobIDs));
        foreach($SnapinTasks AS $i => &$SnapinTask) {
            $Snapin = $SnapinTask->getSnapin();
            if ($Snapin->isValid()) {
                $this->data[] = array(
                    snapin_name=>$Snapin->get(name),
                    snapin_start=>$this->formatTime($SnapinTask->get(checkin)),
                    snapin_end => $this->formatTime($SnapinTask->get(complete)),
                    snapin_duration => $this->diff($SnapinTask->get(checkin),$SnapinTask->get('complete')),
                    snapin_return=>$SnapinTask->get('return'),
                );
            }
        }
        unset($SnapinTask);
        // Hook
        $this->HookManager->processEvent(HOST_SNAPIN_HIST,array(headerData=>&$this->headerData,data=>&$this->data,templates=>&$this->templates,attributes=>&$this->attributes));
        // Output
        $this->render();
        print '</div></div>';
    }
    /** edit_post()
        Actually saves the data.
     */
    public function edit_post() {
        // Find
        $HostManager = $this->getClass('HostManager');
        $Inventory = $this->obj->get(inventory);
        // Hook
        $this->HookManager->processEvent('HOST_EDIT_POST', array('Host' => &$Host));
        // POST
        try {
            // Tabs
            switch ($_REQUEST[tab]) {
                case 'host-general';
                $hostName = trim($_REQUEST['host']);
                // Error checking
                if (empty($hostName)) throw new Exception('Please enter a hostname');
                if ($this->obj->get('name') != $hostName && !$this->obj->isHostnameSafe($hostName)) throw new Exception(_('Please enter a valid hostname'));
                if ($this->obj->get('name') != $hostName && $this->obj->getManager()->exists($hostName)) throw new Exception('Hostname Exists already');
                if (empty($_REQUEST['mac'])) throw new Exception('MAC Address is required');
                // Variables
                $mac = $this->getClass(MACAddress,$_REQUEST[mac]);
                // Task variable.
                $Task = $this->obj->get(task);
                // Error checking
                if (!$mac->isValid()) throw new Exception(_('MAC Address is not valid'));
                if ((!$_REQUEST[image] && $Task->isValid()) || ($_REQUEST[image] && $_REQUEST[image] != $this->obj->get(imageID) && $Task->isValid())) throw new Exception('Cannot unset image.<br />Host is currently in a tasking.');
                // Define new Image object with data provided

                $this->obj
                    ->set(name,$hostName)
                    ->set(description,$_REQUEST[description])
                    ->set(imageID,$_REQUEST[image])
                    ->set(kernel,$_REQUEST[kern])
                    ->set(kernelArgs,$_REQUEST[args])
                    ->set(kernelDevice,$_REQUEST[dev])
                    ->set(productKey,base64_encode($_REQUEST['key']));
                if (strtolower($this->obj->getMACAddress()) != strtolower($mac->__toString()))
                    $this->obj->set(mac, strtolower($mac->__toString()));
                $MyMACs = $AddMe = array();
                foreach((array)$_REQUEST[additionalMACs] AS $i => &$MAC) {
                    $MAC = (!($MAC instanceof MACAddress) ? $this->getClass(MACAddress,$MAC) : $MAC);
                    if ($MAC && $MAC->isValid()) $AddMe[] = strtolower($MAC->__toString());
                }
                unset($MAC);
                foreach($this->obj->get(additionalMACs) AS $i => &$MyMAC) {
                    if ($MyMAC instanceof MACAddress && $MyMAC->isValid()) $MyMACs[] = strtolower($MyMAC->__toString());
                }
                unset($MyMAC);
                if (isset($_REQUEST[primaryMAC])) {
                    $AddMe[] = strtolower($mac->__toString());
                    $this->obj
                        ->removeAddMAC($_REQUEST[primaryMAC])
                        ->set(mac, strtolower($_REQUEST[primaryMAC]));
                }
                $AddMe = array_diff((array)$AddMe,(array)$MyMACs);
                if (count($AddMe)) $this->obj->addAddMAC($AddMe);
                if(isset($_REQUEST[additionalMACsRM])) $this->obj->removeAddMAC($_REQUEST[additionalMACsRM]);
                break;
                case 'host-active-directory';
                $useAD = isset($_REQUEST[domain]);
                $domain = trim($_REQUEST[domainname]);
                $ou = trim($_REQUEST[ou]);
                $user = trim($_REQUEST[domainuser]);
                $pass = trim($_REQUEST[domainpassword]);
                $passlegacy = trim($_REQUEST[domainpasswordlegacy]);
                $this->obj->setAD($useAD,$domain,$ou,$user,$pass,true,false,$passlegacy);
                break;
                case 'host-printers';
                $PrinterManager = $this->getClass(PrinterAssociationManager);
                // Set printer level for Host
                if (isset($_REQUEST[level]))
                    $this->obj->set(printerLevel,$_REQUEST[level]);
                // Add
                if (isset($_REQUEST[updateprinters])) {
                    $this->obj->addPrinter($_REQUEST[printer]);
                    // Set Default
                    foreach($_REQUEST[printerid] AS $i => &$printerid) $this->obj->updateDefault($_REQUEST['default'],isset($_REQUEST['default']));
                    unset($printerid);
                }
                // Remove
                if (isset($_REQUEST[printdel]))
                    $this->obj->removePrinter($_REQUEST[printerRemove]);
                break;
                case 'host-snapins';
                // Add
                if (!isset($_REQUEST[snapinRemove]))
                    $this->obj->addSnapin($_REQUEST[snapin]);
                // Remove
                if (isset($_REQUEST[snaprem]))
                    $this->obj->removeSnapin($_REQUEST[snapinRemove]);
                break;
                case 'host-service';
                // be set to the default values within the system.
                $x =(is_numeric($_REQUEST[x]) ? $_REQUEST[x] : $this->FOGCore->getSetting(FOG_SERVICE_DISPLAYMANAGER_X));
                $y =(is_numeric($_REQUEST[y]) ? $_REQUEST[y] : $this->FOGCore->getSetting(FOG_SERVICE_DISPLAYMANAGER_Y));
                $r =(is_numeric($_REQUEST[r]) ? $_REQUEST[r] : $this->FOGCore->getSetting(FOG_SERVICE_DISPLAYMANAGER_R));
                $tme = (is_numeric($_REQUEST[tme]) ? $_REQUEST[tme] : $this->FOGCore->getSetting(FOG_SERVICE_AUTOLOGOFF_MIN));
                if (isset($_REQUEST[updatestatus])) {
                    $modOn = $_REQUEST[modules];
                    $modOff = $this->getClass(ModuleManager)->find(array('id' => $modOn),'','','','','',true,'id');
                    if (count($modOn)) $this->obj->addModule($modOn);
                    if (count($modOff)) $this->obj->removeModule($modOff);
                }
                if (isset($_REQUEST[updatedisplay]))
                    $this->obj->setDisp($x,$y,$r);
                if (isset($_REQUEST[updatealo])) $this->obj->setAlo($tme);
                break;
                case 'host-hardware-inventory';
                $pu = trim($_REQUEST[pu]);
                $other1 = trim($_REQUEST[other1]);
                $other2 = trim($_REQUEST[other2]);
                if ($_REQUEST[update] == 1) {
                    $Inventory->set(primaryUser,$pu)
                        ->set(other1,$other1)
                        ->set(other2,$other2)
                        ->save();
                }
                break;
                case 'host-login-history';
                $this->FOGCore->redirect("?node=host&sub=edit&id=".$this->obj->get(id)."&dte=".$_REQUEST[dte]."#".$_REQUEST[tab]);
                break;
                case 'host-virus-history';
                if (isset($_REQUEST[delvid]) && $_REQUEST[delvid] == 'all') {
                    $this->obj->clearAVRecordsForHost();
                    $this->FOGCore->redirect('?node=host&sub=edit&id='.$this->obj->get(id).'#'.$_REQUEST[tab]);
                }
                else if (isset($_REQUEST[delvid])) $this->getClass(VirusManager)->destroy(array('id' => $_REQUEST[delvid]));
                break;
            }
            // Save to database
            if ($this->obj->save()) {
                $this->obj->setAD();
                if ($_REQUEST[tab] == 'host-general') $this->obj->ignore($_REQUEST[igimage],$_REQUEST[igclient]);
                // Hook
                $this->HookManager->processEvent('HOST_EDIT_SUCCESS', array('Host' => &$this->obj));
                // Log History event
                $this->FOGCore->logHistory('Host updated: ID: '.$this->obj->get(id).', Name: '.$this->obj->get(name).', Tab: '.$_REQUEST[tab]);
                // Set session message
                $this->FOGCore->setMessage('Host updated!');
                // Redirect to new entry
                $this->FOGCore->redirect(sprintf('?node=%s&sub=edit&%s=%s#%s', $this->REQUEST['node'], $this->id, $this->obj->get(id), $_REQUEST[tab]));
            } else throw new Exception('Host update failed');
        } catch (Exception $e) {
            // Hook
            $this->HookManager->processEvent('HOST_EDIT_FAIL', array('Host' => &$this->obj));
            // Log History event
            $this->FOGCore->logHistory('Host update failed: Name: '.$_REQUEST[name].', Tab: '.$_REQUEST[tab].', Error: '.$e->getMessage());
            // Set session message
            $this->FOGCore->setMessage($e->getMessage());
            // Redirect
            $this->FOGCore->redirect('?node=host&sub=edit&id='.$this->obj->get(id).'#'.$_REQUEST[tab]);
        }
    }
    /** import()
        Import host form.
     */
    public function import() {
        // Title
        $this->title = 'Import Host List';
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
        print "\n\t\t\t".'<form enctype="multipart/form-data" method="post" action="'.$this->formAction.'">';
        $fields = array(
            _('CSV File') => '<input class="smaller" type="file" name="file" />',
            '&nbsp;' => '<input class="smaller" type="submit" value="'._('Upload CSV').'" />',
        );
        foreach ((array)$fields AS $field => $input) {
            $this->data[] = array(
                'field' => $field,
                'input' => $input,
            );
        }
        // Hook
        $this->HookManager->processEvent('HOST_IMPORT_OUT', array('headerData' => &$this->headerData, 'data' => &$this->data, 'templates' => &$this->templates, 'attributes' => &$this->attributes));
        // Output
        $this->render();
        print "</form>";
        print "\n\t\t\t<p>"._('This page allows you to upload a CSV file of hosts into FOG to ease migration.  Right click').' <a href="./other/hostimport.csv">'._('here').'</a>'._(' and select ').'<strong>'._('Save target as...').'</strong>'._(' or ').'<strong>'.('Save link as...').'</strong>'._(' to download a template file.  The only fields that are required are hostname and MAC address.  Do ').'<strong>'._('NOT').'</strong>'._(' include a header row, and make sure you resave the file as a CSV file and not XLS!').'</p>';
    }
    /** import_post()
        Actually imports the post.
     */
    public function import_post() {
        try {
            // Error checking
            if ($_FILES["file"]["error"] > 0) throw new Exception(sprintf('Error: '.(is_array($_FILES["file"]["error"]) ? implode(', ', $_FILES["file"]["error"]) : $_FILES["file"]["error"])));
            if (!file_exists($_FILES['file']['tmp_name'])) throw new Exception('Could not find tmp filename');
            $numSuccess = $numFailed = $numAlreadyExist = 0;
            $handle = fopen($_FILES["file"]["tmp_name"], "r");
            // Get all the service id's so they can be enabled.
            $ModuleIDs = $this->getClass('ModuleManager')->find('','','','','','','','id');
            while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                // Ignore header data if left in CSV
                if (preg_match('#ie#',$data[0])) continue;
                $totalRows++;
                if (count($data) < 7 && count($data) >= 2) {
                    try {
                        // Error checking
                        $MACs = $this->parseMacList($data[0]);
                        $PriMAC = array_shift($MACs);
                        $Host = $this->getClass(HostManager)->getHostByMacAddresses($MACs);
                        if ($Host && $Host->isValid()) throw new Exception('A Host with any or one of these MACs already exists');
                        if ($this->getClass(HostManager)->exists($data[1])) throw new Exception('A host with this name already exists');
                        $Host = $this->getClass(Host)
                            ->set(name,$data[1])
                            ->set(description,$data[3].' Updated by batch import on '.$this->nice_date()->format('Y-m-d H:i:s'))
                            ->set(ip,$data[2])
                            ->set(imageID,$data[4])
                            ->set(createdTime,$this->nice_date()->format('Y-m-d H:i:s'))
                            ->set(createBy,$this->FOGUser->get(name))
                            ->addModule($ModuleIDs)
                            ->addPriMAC($PriMAC)
                            ->addAddMAC($MACs);
                        unset($MACs);
                        if ($Host->save()) {
                            $this->HookManager->processEvent('HOST_IMPORT',array('data' => &$data,'Host' => &$Host));
                            $numSuccess++;
                        } else $numFailed++;
                    } catch (Exception $e) {
                        $numFailed++;
                        $uploadErrors .= sprintf('%s #%s: %s<br />', _('Row'), $totalRows, $e->getMessage());
                    }
                } else {
                    $numFailed++;
                    $uploadErrors .= sprintf('%s #%s: %s<br />', _('Row'), $totalRows, _('Invalid number of cells'));
                }
            }
            fclose($handle);
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        // Title
        $this->title = _('Import Host Results');
        unset($this->headerData);
        $this->attributes = array(
            array(),
            array(),
        );
        $this->templates = array(
            '${field}',
            '${input}',
        );
        $fields = array(
            _('Total Rows') => $totalRows,
            _('Successful Hosts') => $numSuccess,
            _('Failed Hosts') => $numFailed,
            _('Errors') => $uploadErrors,
        );

        foreach((array)$fields AS $field => &$input) {
            $this->data[] = array(
                'field' => $field,
                'input' => $input,
            );
        }
        unset($input);
        // Hook
        $this->HookManager->processEvent('HOST_IMPORT_FIELDS', array('headerData' => &$this->headerData, 'data' => &$this->data, 'templates' => &$this->templates, 'attributes' => &$this->attributes));
        // Output
        $this->render();
    }
    /** export()
        Exports the hosts from the database.
     */
    public function export() {
        $this->title = 'Export Hosts';
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
            _('Click the button to download the hosts table backup.') => '<input type="submit" value="'._('Export').'" />',
        );
        $report = new ReportMaker();
        $Hosts = $this->getClass(HostManager)->find();
        foreach($Hosts AS $i => &$Host) {
            $macs[] = $Host->get(mac);
            foreach($Host->get(additionalMACs) AS $i => $AddMAC) {
                if ($AddMAC && $AddMAC->isValid()) $macs[] = $AddMAC->__toString();
            }
            $report->addCSVCell(implode('|',(array)$macs));
            $report->addCSVCell($Host->get(name));
            $report->addCSVCell($Host->get(ip));
            $report->addCSVCell('"'.$Host->get(description).'"');
            $report->addCSVCell($Host->get(imageID));
            $this->HookManager->processEvent(HOST_EXPORT_REPORT,array(report=>&$report,Host=>&$Host));
            $report->endCSVLine();
            unset($macs);
        }
        unset($Host);
        $_SESSION[foglastreport]=serialize($report);
        print '<form method="post" action="export.php?type=host">';
        foreach ((array)$fields AS $field => $input) {
            $this->data[] = array(
                'field' => $field,
                'input' => $input,
            );
        }
        // Hook
        $this->HookManager->processEvent(HOST_EXPORT,array(headerData=>&$this->headerData,data=>&$this->data,templates=>&$this->templates,attributes=>&$this->attributes));
        // Output
        $this->render();
        print "</form>";
    }
    /** save_group()
        Saves the data to a host.
     */
    public function save_group() {
        try {
            // Error checking
            if (empty($_REQUEST[hostIDArray])) throw new Exception(_('No Hosts were selected'));
            if (empty($_REQUEST[group_new]) && empty($_REQUEST[group])) throw new Exception(_('No Group selected and no new Group name entered'));
            // Determine which method to use
            // New group
            if (!empty($_REQUEST[group_new])) {
                $Group = $this->getClass(Group)
                    ->set(name,$_REQUEST[group_new]);
                if (!$Group->save()) throw new Exception(_('Failed to create new Group'));
            } else $Group = $this->getClass(Group,$_REQUEST[group]);
            // Valid
            if (!$Group->isValid()) throw new Exception(_('Group is Invalid'));
            // Main
            $Group->addHost(explode(',',$_REQUEST[hostIDArray]))->save();
            // Success
            print '<div class="task-start-ok"><p>'._('Successfully associated Hosts with the Group ').$Group->get('name').'</p></div>';
        } catch (Exception $e) {
            printf('<div class="task-start-failed"><p>%s</p><p>%s</p></div>', _('Failed to Associate Hosts with Group'), $e->getMessage());
        }
    }
    public function hostlogins() {
        $MainDate = $this->nice_date($_REQUEST[dte])->getTimestamp();
        $MainDate_1 = $this->nice_date($_REQUEST[dte])->modify('+1 day')->getTimestamp();
        $Users = $this->getClass(UserTrackingManager)->find(array(hostID=>$_REQUEST[id],'date'=>$_REQUEST[dte],action=>array(null,0,1)),'','date','DESC');
        foreach($Users AS $i => &$Login) {
            if ($Login->get(username) != 'Array') {
                $time = $this->nice_date($Login->get(datetime))->format('U');
                if (!$Data[$Login->get('username')]) $Data[$Login->get(username)] = array(user=>$Login->get(username),'min'=>$MainDate,'max'=>$MainDate_1);
                if ($Login->get('action')) $Data[$Login->get(username)][login] = $time;
                if (array_key_exists('login',$Data[$Login->get('username')]) && !$Login->get('action')) $Data[$Login->get('username')]['logout'] = $time;
                if (array_key_exists('login',$Data[$Login->get('username')]) && array_key_exists('logout',$Data[$Login->get('username')])) {
                    $data[] = $Data[$Login->get('username')];
                    unset($Data[$Login->get('username')]);
                }
            }
        }
        unset($Login);
        print json_encode($data);
    }
}
