<?php

namespace Kanboard\ServiceProvider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Kanboard\Core\Security\AuthenticationManager;
use Kanboard\Core\Security\AccessMap;
use Kanboard\Core\Security\Authorization;
use Kanboard\Core\Security\Role;
use Kanboard\Auth\RememberMeAuth;
use Kanboard\Auth\DatabaseAuth;
use Kanboard\Auth\LdapAuth;
use Kanboard\Auth\GitlabAuth;
use Kanboard\Auth\GithubAuth;
use Kanboard\Auth\GoogleAuth;

/**
 * Authentication Provider
 *
 * @package serviceProvider
 * @author  Frederic Guillot
 */
class AuthenticationProvider implements ServiceProviderInterface
{
    /**
     * Register providers
     *
     * @access public
     * @param  \Pimple\Container $container
     * @return \Pimple\Container
     */
    public function register(Container $container)
    {
        $container['authenticationManager'] = new AuthenticationManager($container);
        $container['authenticationManager']->register(new RememberMeAuth($container));

        if (LDAP_AUTH) {
            $container['authenticationManager']->register(new LdapAuth($container));
        }

        if (GITLAB_AUTH) {
            $container['authenticationManager']->register(new GitlabAuth($container));
        }

        if (GITHUB_AUTH) {
            $container['authenticationManager']->register(new GithubAuth($container));
        }

        if (GOOGLE_AUTH) {
            $container['authenticationManager']->register(new GoogleAuth($container));
        }

        $container['authenticationManager']->register(new DatabaseAuth($container));

        $container['projectAccessMap'] = $this->getProjectAccessMap();
        $container['applicationAccessMap'] = $this->getApplicationAccessMap();

        $container['projectAuthorization'] = new Authorization($container['projectAccessMap']);
        $container['applicationAuthorization'] = new Authorization($container['applicationAccessMap']);

        return $container;
    }

    /**
     * Get ACL for projects
     *
     * @access public
     * @return AccessMap
     */
    public function getProjectAccessMap()
    {
        $acl = new AccessMap;
        $acl->setDefaultRole(Role::PROJECT_VIEWER);
        $acl->setRoleHierarchy(Role::PROJECT_MANAGER, array(Role::PROJECT_MEMBER, Role::PROJECT_VIEWER));
        $acl->setRoleHierarchy(Role::PROJECT_MEMBER, array(Role::PROJECT_VIEWER));

        $acl->add('Action', '*', Role::PROJECT_MANAGER);
        $acl->add('Analytic', '*', Role::PROJECT_MANAGER);
        $acl->add('Board', array('save', 'changeAssignee', 'updateAssignee', 'changeCategory', 'updateCategory'), Role::PROJECT_MEMBER);
        $acl->add('Calendar', 'save', Role::PROJECT_MEMBER);
        $acl->add('Category', '*', Role::PROJECT_MANAGER);
        $acl->add('Column', '*', Role::PROJECT_MANAGER);
        $acl->add('Comment', '*', Role::PROJECT_MEMBER);
        $acl->add('Customfilter', '*', Role::PROJECT_MEMBER);
        $acl->add('Export', '*', Role::PROJECT_MANAGER);
        $acl->add('File', array('screenshot', 'create', 'save', 'remove', 'confirm'), Role::PROJECT_MEMBER);
        $acl->add('Gantt', '*', Role::PROJECT_MANAGER);
        $acl->add('Project', array('share', 'integrations', 'notifications', 'edit', 'update', 'users', 'allowEverybody', 'allow', 'role', 'revoke', 'duplicate', 'disable', 'enable'), Role::PROJECT_MANAGER);
        $acl->add('Projectinfo', '*', Role::PROJECT_MANAGER);
        $acl->add('Subtask', '*', Role::PROJECT_MEMBER);
        $acl->add('Swimlane', '*', Role::PROJECT_MANAGER);
        $acl->add('Task', 'remove', Role::PROJECT_MEMBER);
        $acl->add('Taskcreation', '*', Role::PROJECT_MEMBER);
        $acl->add('Taskmodification', '*', Role::PROJECT_MEMBER);
        $acl->add('Timer', '*', Role::PROJECT_MEMBER);

        return $acl;
    }

    /**
     * Get ACL for the application
     *
     * @access public
     * @return AccessMap
     */
    public function getApplicationAccessMap()
    {
        $acl = new AccessMap;
        $acl->setDefaultRole(Role::APP_USER);
        $acl->setRoleHierarchy(Role::APP_ADMIN, array(Role::APP_MANAGER, Role::APP_USER, Role::APP_PUBLIC));
        $acl->setRoleHierarchy(Role::APP_MANAGER, array(Role::APP_USER, Role::APP_PUBLIC));
        $acl->setRoleHierarchy(Role::APP_USER, array(Role::APP_PUBLIC));

        $acl->add('Oauth', array('google', 'github', 'gitlab'), Role::APP_PUBLIC);
        $acl->add('Auth', array('login', 'check', 'captcha'), Role::APP_PUBLIC);
        $acl->add('Webhook', '*', Role::APP_PUBLIC);
        $acl->add('Task', 'readonly', Role::APP_PUBLIC);
        $acl->add('Board', 'readonly', Role::APP_PUBLIC);
        $acl->add('Ical', '*', Role::APP_PUBLIC);
        $acl->add('Feed', '*', Role::APP_PUBLIC);

        $acl->add('Config', '*', Role::APP_ADMIN);
        $acl->add('Currency', '*', Role::APP_ADMIN);
        $acl->add('Gantt', '*', Role::APP_MANAGER);
        $acl->add('Group', '*', Role::APP_ADMIN);
        $acl->add('Link', '*', Role::APP_ADMIN);
        $acl->add('Project', array('users', 'allowEverybody', 'allow', 'role', 'revoke', 'remove'), Role::APP_MANAGER);
        $acl->add('Projectuser', '*', Role::APP_MANAGER);
        $acl->add('Twofactor', 'disable', Role::APP_ADMIN);
        $acl->add('UserImport', '*', Role::APP_ADMIN);
        $acl->add('User', array('index', 'create', 'save', 'authentication', 'remove'), Role::APP_ADMIN);

        return $acl;
    }
}
