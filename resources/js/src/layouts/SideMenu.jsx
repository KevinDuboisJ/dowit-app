import React from 'react'
import classnames from 'classnames'
import { userHasPathAccess } from '@/stores/side-menu'
import { Heroicon, Lucide, Tooltip, Notification } from '@/base-components'
import Logo from '@images/logo.png'
import { UserMenu } from '@/components'

export const SideMenu = React.memo(
  ({
    user,
    sideMenuLinks,
    mobileMenu,
    handleLinkClick,
    activeDropdowns,
    fontSize,
    setFontSize
  }) => {
    return (
      <nav
        className={classnames({
          'side-nav': true,
          'side-nav--active': mobileMenu
        })}
      >
        <div className="pt-4 mb-4">
          <div className="side-nav__header flex items-center">
            <a href="/" className="fadeInRight z-50 flex items-center">
              <img
                alt="Spacem²"
                className="side-nav__header__logo"
                src={Logo}
                width="100px"
              />
            </a>
          </div>
        </div>
        <div className="scrollable">
          <ul className="scrollable__content">
            {/* BEGIN: First Child */}
            {sideMenuLinks.map((menu, menuKey) => {
              const hasAccess = userHasPathAccess(user, menu)

              // Skip rendering if no access.
              if (!hasAccess) return null

              return menu?.type === 'title' ? (
                <li className="side-nav__devider mb-4" key={menu + menuKey}>
                  {menu.title}
                </li>
              ) : (
                <li key={menu + menuKey}>
                  <a
                    href={menu.subMenu ? '#' : menu.pathname}
                    className={classnames({
                      'side-menu': true,
                      'side-menu--active': menu.active,
                      'side-menu--open':
                        activeDropdowns[menu.title || menu.pathname]
                    })}
                    onClick={event =>
                      handleLinkClick(event, menu, menu.pathname)
                    }
                  >
                    <div className="side-menu__icon">
                      <Heroicon icon={menu.icon} />
                    </div>
                    <div className="side-menu__title">
                      {menu.title}
                      {menu.subMenu && (
                        <div
                          className={classnames({
                            'side-menu__sub-icon': true,
                            'transform rotate-180':
                              activeDropdowns[menu.title || menu.pathname]
                          })}
                        >
                          <Lucide icon="ChevronDown" />
                        </div>
                      )}
                    </div>
                  </a>

                  {/* BEGIN: Second Child */}
                  {menu.subMenu && (
                    <ul
                      className={classnames({
                        'side-menu__sub-open':
                          activeDropdowns[menu.title || menu.pathname]
                      })}
                    >
                      {menu.subMenu.map((subMenu, subMenuKey) => {
                        const hasAccess = userHasPathAccess(user, subMenu)

                        // Skip rendering if no access.
                        if (!hasAccess) return null

                        return (
                          <li key={subMenuKey}>
                            <a
                              href={subMenu.subMenu ? '#' : subMenu.pathname}
                              className={classnames({
                                'side-menu': true,
                                'side-menu--active': subMenu.active
                              })}
                              onClick={event =>
                                handleLinkClick(
                                  event,
                                  subMenu,
                                  subMenu.pathname
                                )
                              }
                            >
                              <div className="side-menu__icon">
                                <Heroicon icon={subMenu.icon} />
                              </div>
                              <div className="side-menu__title">
                                {subMenu.title}
                                {subMenu.subMenu && (
                                  <div
                                    className={classnames({
                                      'side-menu__sub-icon': true,
                                      'transform rotate-180':
                                        activeDropdowns[
                                          subMenu.title || subMenu.pathname
                                        ]
                                    })}
                                  >
                                    <Lucide icon="ChevronDown" />
                                  </div>
                                )}
                              </div>
                            </a>
                            {/* BEGIN: Third Child */}
                            {subMenu.subMenu && (
                              <ul
                                className={classnames({
                                  'side-menu__sub-open':
                                    activeDropdowns[
                                      subMenu.title || subMenu.pathname
                                    ]
                                })}
                              >
                                {subMenu.subMenu.map(
                                  (lastSubMenu, lastSubMenuKey) => (
                                    <li key={lastSubMenuKey}>
                                      <a
                                        href={
                                          lastSubMenu.subMenu
                                            ? '#'
                                            : lastSubMenu.pathname
                                        }
                                        className={classnames({
                                          'side-menu': true,
                                          'side-menu--active':
                                            lastSubMenu.active
                                        })}
                                        onClick={event =>
                                          handleLinkClick(
                                            event,
                                            lastSubMenu,
                                            lastSubMenu.pathname
                                          )
                                        }
                                      >
                                        <div className="side-menu__icon">
                                          <Heroicon icon={lastSubMenu.icon} />
                                        </div>
                                        <div className="side-menu__title">
                                          {lastSubMenu.title}
                                        </div>
                                      </a>
                                    </li>
                                  )
                                )}
                              </ul>
                            )}
                            {/* END: Third Child */}
                          </li>
                        )
                      })}
                    </ul>
                  )}
                  {/* END: Second Child */}
                </li>
              )
            })}
            {/* END: First Child */}
          </ul>
        </div>
        <div className="hidden xl:flex flex-col z-50 py-6 border-t border-gray-700">
          <div className="flex items-center gap-4">
            <UserMenu
              user={user}
              fontSize={fontSize}
              setFontSize={setFontSize}
            />
            <Notification user={user} />
          </div>
          {/* <Tooltip
            content="Deze webapplicatie is ontwikkeld door Kevin Dubois"
            asChild
          >
            <span
              className="pt-2 cursor-pointer text-right text-white/40 font-light text-[8px]"
              alt="about"
            >
              about v0.9.9
            </span>
          </Tooltip> */}
        </div>
      </nav>
    )
  }
)
