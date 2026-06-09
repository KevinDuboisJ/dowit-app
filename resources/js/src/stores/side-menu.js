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
      title: 'Bestanden',
      pathname: '/assets'
    },

    {
      type: 'title',
      title: 'ADMIN',
      roles: ['1', '2']
    },

    {
      icon: 'Squares2X2',
      pathname: '/beds',
      title: 'Bedden',
      roles: ['1', '2']
    },

    {
      icon: 'ListBullet',
      pathname: 'https://formbuilder.monica.be/?page=form&fo_id=59',
      title: 'Controlelijst',
      teams: ['3', '9']
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
          title: 'Taakplanner'
        },
        {
          icon: 'RectangleStack',
          pathname: '/adm/holidays',
          roles: ['1', '2'],
          title: 'Feestdagen'
        }
      ]
    }
  ]
}

const userHasPathAccess = (user, menu) => {
  const menuRoleSet = new Set(menu?.roles ?? [])
  const menuTeamSet = new Set(menu?.teams ?? [])

  const hasRoleForThis = Object.keys(user?.roles ?? {}).some(role =>
    menuRoleSet.has(role)
  )

  const hasTeamForThis = (user?.teams ?? []).some(team =>
    menuTeamSet.has(String(team.id))
  )

  return hasRoleForThis || hasTeamForThis || (!menu?.roles && !menu?.teams)
}

export { sideMenu, userHasPathAccess }
