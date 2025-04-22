const sideMenu = {
  key: 'sideMenu',
  menu: [
    {
      type: 'title',
      title: 'MENU'
    },
    {
      icon: 'Home',
      title: 'Dashboard',
      pathname: '/'
    },
    {
      icon: 'Newspaper',
      title: 'Newsfeed',
      pathname: '/newsfeed'
    },
    {
      icon: 'DocumentText',
      title: 'Documenten',
      pathname: '/documents'
    },
    {
      type: 'title',
      title: 'ADMIN',
      roles: ['1', '2']
    },
    {
      icon: 'Cog6Tooth',
      pathname: '/adm',
      title: 'Admin paneel',
      roles: ['1', '2'],
      subMenu: [
        {
          icon: 'AdjustmentsHorizontal',
          pathname: '/adm/task-planners',
          roles: ['1', '2'],
          title: 'Taakconfigurator'
        },
      ]
    }
  ]

}

const userHasPathAccess = (user, menu) => {

  // Create a Set from menu roles for efficient lookup.
  const myRoleSet = new Set(menu?.roles);

  // Check if any value from user roles exists in menu roles using Set lookup.
  const hasRoleForThis = Object.keys(user.roles).some(value => myRoleSet.has(value));

  // Show the item if the user has the required role or if no roles are defined in the menu item.
  return hasRoleForThis || !('roles' in menu);
}

export { sideMenu, userHasPathAccess }
