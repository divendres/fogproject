<?php
class UserManagementPage extends FOGPage {
    public $node = 'user';
    // __construct
    public function __construct($name = '') {
        $this->name = 'User Management';
        // Call parent constructor
        parent::__construct($this->name);
        if ($_REQUEST['id']) {
            $this->subMenu = array(
                $this->linkformat => $this->foglang['General'],
                $this->delformat => $this->foglang['Delete'],
            );
            $this->obj = $this->getClass('User',$_REQUEST['id']);
            $this->notes = array(
                $this->foglang['User'] => $this->obj->get('name')
            );
        }
        $this->HookManager->processEvent('SUB_MENULINK_DATA',array('menu' => &$this->menu,'submenu' => &$this->subMenu,'id' => &$this->id,'notes' => &$this->notes));
        // Header row
        $this->headerData = array(
            '<input type="checkbox" name="toggle-checkbox" class="toggle-checkboxAction" />',
            _('Username'),
            _('Edit')
        );
        // Row templates
        $this->templates = array(
            '<input type="checkbox" name="user[]" value="${id}" class="toggle-action" />',
            sprintf('<a href="?node=%s&sub=edit&%s=${id}" title="%s">${name}</a>', $this->node, $this->id, _('Edit User')),
            sprintf('<a href="?node=%s&sub=edit&%s=${id}" title="%s"><i class="icon fa fa-pencil"></i></a>', $this->node, $this->id, _('Edit User'))
        );
        // Row attributes
        $this->attributes = array(
            array('class' => 'c', 'width' => '16'),
            array(),
            array('class' => 'c', 'width' => '55'),
        );
    }
    // Pages
    public function index() {
        // Set title
        $this->title = _('All Users');
        if ($_SESSION[DataReturn] > 0 && $_SESSION[UserCount] > $_SESSION[DataReturn] && $_REQUEST['sub'] != 'list') $this->FOGCore->redirect(sprintf('%s?node=%s&sub=search', $_SERVER[PHP_SELF], $this->node));
        // Find data
        $Users = $this->getClass(UserManager)->find();
        // Row data
        foreach ($Users AS $i => &$User) {
            if ($User->isValid()) {
                $this->data[] = array(
                    id=>$User->get(id),
                    name=>$User->get(name)
                );
            }
        }
        unset($User);
        // Hook
        $this->HookManager->processEvent(USER_DATA,array(headerData=>&$this->headerData,data=>&$this->data,templates=>&$this->templates,attributes=>&$this->attributes));
        // Output
        $this->render();
    }
    public function search() {
        // Set title
        $this->title = _('Search');
        // Set search form
        $this->searchFormURL = sprintf('%s?node=%s&sub=search',$_SERVER[PHP_SELF],$this->node);
        // Hook
        $this->HookManager->processEvent(USER_SEARCH);
        // Output
        $this->render();
    }
    public function search_post() {
        // Find data -> Push data
        $Users = $this->getClass(UserManager)->search();
        foreach ($Users AS $i => &$User) {
            $this->data[] = array(
                id=>$User->get(id),
                name=>$User->get(name)
            );
        }
        unset($User);
        // Hook
        $this->HookManager->processEvent(USER_DATA,array(headerData=>&$this->headerData,data=>&$this->data,templates=>&$this->templates,attributes=>&$this->attributes));
        // Output
        $this->render();
    }
    public function add() {
        // Set title
        $this->title = _('New User');
        unset ($this->headerData);
        $this->templates = array(
            '${field}',
            '${input}',
        );
        $this->attributes = array(
            array(),
            array(),
        );
        $fields = array(
            _('User Name') => '<input type="text" name="name" value="'.$_REQUEST[name].'" autocomplete="off" />',
            _('User Password') => '<input type="password" name="password" value="" autocomplete="off" />',
            _('User Password (confirm)') => '<input type="password" name="password_confirm" value="" autocomplete="off" />',
            _('Mobile/Quick Image Access Only?').'&nbsp;'.'<span class="icon icon-help hand" title="'._('Warning - if you tick this box, this user will not be able to log into this FOG Management Console in the future.').'"></span>' => '<input type="checkbox" name="isGuest" autocomplete="off" />',
            '&nbsp;' => '<input type="submit" value="'._('Create User').'" />',
        );
        print "<h2>"._('Add new user account').'</h2>';
        print '<form method="post" action="'.$this->formAction.'">';
        print '<input type="hidden" name="add" value="1" />';
        foreach ((array)$fields AS $field => &$input) {
            $this->data[] = array(
                'field' => $field,
                'input' => $input,
            );
        }
        unset($input);
        $this->HookManager->processEvent(USER_ADD,array(data=>&$this->data,templates=>&$this->templates,attributes=>&$this->attributes));
        $this->render();
        print "</form>";
    }
    public function add_post() {
        // Hook
        $this->HookManager->processEvent(USER_ADD_POST);
        // POST
        try {
            // Error checking
            if ($this->getClass(UserManager)->exists($_REQUEST[name])) throw new Exception(_('Username already exists'));
            if (!$this->getClass(UserManager)->isPasswordValid($_REQUEST[password],$_REQUEST[password_confirm])) throw new Exception(_('Password is invalid'));
            // Create new Object
            $User = $this->getClass(User);
            $User->set(name,$_REQUEST[name])
                ->set(type,isset($_REQUEST[isGuest]))
                ->set(password,$_REQUEST[password]);
            // Save
            if (!$User->save()) throw new Exception(_('Failed to create user'));
            // Hook
            $this->HookManager->processEvent(USER_ADD_SUCCESS,array(User=>&$User));
            // Log History event
            $this->FOGCore->logHistory(sprintf('%s: ID: %s, Name: %s', _('User created'),$User->get(id),$User->get(name)));
            // Set session message
            $this->FOGCore->setMessage(_('User created').'<br>'._('You may now create another'));
            // Redirect to new entry
            $this->FOGCore->redirect(sprintf('?node=%s&sub=add',$this->request[node],$this->id,$User->get(id)));
        } catch (Exception $e) {
            // Hook
            $this->HookManager->processEvent(USER_ADD_FAIL,array(User=>&$User));
            // Log History event
            $this->FOGCore->logHistory(sprintf('%s add failed: Name: %s, Error: %s',_('User'),$_REQUEST[name],$e->getMessage()));
            // Set session message
            $this->FOGCore->setMessage($e->getMessage());
            // Redirect to new entry
            $this->FOGCore->redirect($this->formAction);
        }
    }
    public function edit() {
        // Find
        $User = $this->obj;
        // Title
        $this->title = sprintf('%s: %s',_('Edit'),$User->get(name));
        $fields = array(
            _('User Name') => '<input type="text" name="name" value="'.$User->get('name').'" />',
            _('New Password') => '<input type="password" name="password" value="" />',
            _('New Password (confirm)') => '<input type="password" name="password_confirm" value="" />',
            _('Mobile/Quick Image Access Only?').'&nbsp;'.'<span class="icon icon-help hand" title="'._('Warning - if you tick this box, this user     will not be able to log into this FOG Management Console in the future.').'"></span>' => '<input type="checkbox" name="isGuest" '.($User->get('type') == 1 ? 'checked' : '').' />',
            '&nbsp;' => '<input type="submit" value="'._('Update').'" />',
        );
        unset ($this->headerData);
        $this->templates = array(
            '${field}',
            '${formData}',
        );
        $this->attributes = array(
            array(),
            array(),
        );
        print '<form method="post" action="'.$this->formAction.'"><input type="hidden" name="update" value="'.$User->get(id).'" />';
        foreach ((array)$fields AS $field => &$formData) {
            $this->data[] = array(
                'field' => $field,
                'formData' => $formData,
            );
        }
        unset($formData);
        $this->HookManager->processEvent('USER_EDIT', array('data' => &$this->data, 'templates' => &$this->templates, 'attributes' => &$this->attributes));
        $this->render();
        print "\n\t\t\t</form>";
    }
    public function edit_post() {
        // Find
        $User = $this->obj;
        // Hook
        $this->HookManager->processEvent('USER_EDIT_POST', array('User' => &$User));
        // POST
        try {
            // UserManager
            $UserManager = $this->getClass('UserManager');
            // Error checking
            if ($UserManager->exists($_REQUEST['name'], $User->get('id'))) throw new Exception(_('Username already exists'));
            if ($_REQUEST['password'] && $_REQUEST['password_confirm']) {
                if (!$UserManager->isPasswordValid($_REQUEST['password'], $_REQUEST['password_confirm'])) throw new Exception(_('Password is invalid'));
            }
            // Update User Object
            $User->set('name', $_REQUEST['name'])
                ->set('type', ($_REQUEST['isGuest'] == 'on' ? '1' : '0'));
            // Set new password if password was passed
            if ($_REQUEST['password'] && $_REQUEST['password_confirm']) $User->set('password',	$_REQUEST['password']);
            // Save
            if ($User->save()) {
                // Hook
                $this->HookManager->processEvent('USER_UPDATE_SUCCESS', array('User' => &$User));
                // Log History event
                $this->FOGCore->logHistory(sprintf('%s: ID: %s, Name: %s', _('User updated'), $User->get('id'), $User->get('name')));
                // Set session message
                $this->FOGCore->setMessage(_('User updated'));
                // Redirect to new entry
                $this->FOGCore->redirect(sprintf('?node=%s&sub=edit&%s=%s', $this->request['node'], $this->id, $User->get('id')));
            } else throw new Exception('Database update failed');
        } catch (Exception $e) {
            // Hook
            $this->HookManager->processEvent('USER_UPDATE_FAIL', array('User' => &$User));
            // Log History event
            $this->FOGCore->logHistory(sprintf('%s update failed: Name: %s, Error: %s', _('User'), $_REQUEST['name'], $e->getMessage()));
            // Set session message
            $this->FOGCore->setMessage($e->getMessage());
            // Redirect to new entry
            $this->FOGCore->redirect($this->formAction);
        }
    }
}
