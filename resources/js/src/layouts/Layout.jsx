import { useState, useEffect } from 'react';
import { toRaw } from '@/utils';
import {
  sideMenu as useSideMenuStore,
  userHasPathAccess
} from '@/stores/side-menu';
import { useRecoilValue } from 'recoil';
import { nestedMenu } from './layout';
import {
  Lucide,
  Heroicon,
  Toaster
} from '@/base-components';
import dom from '@left4code/tw-starter/dist/js/dom';
import SimpleBar from 'simplebar';
import Logo from '@images/logo.png';
import classnames from 'classnames';
import TopBar from '@/components/top-bar/TopBar';
import { router, usePage } from '@inertiajs/react';
import Footer from '@/components/footer/Footer.jsx';
import { useFontSize, useIsMobile } from '@/hooks';

const Layout = ({ children: { props: { user } }, children }) => {

  const [formattedMenu, setFormattedMenu] = useState([]);
  const { url } = usePage();
  const sideMenuStore = useRecoilValue(useSideMenuStore);
  const sideMenu = () => nestedMenu(toRaw(sideMenuStore.menu), new URL(url, window.location.origin).pathname);
  const [mobileMenu, setMobileMenu] = useState(false);
  const { fontSize, setFontSize } = useFontSize(16); // Default size in pixels
  const { isMobile, hasTransitionedToMobile } = useIsMobile();

  // Set active/inactive mobile menu
  const toggleMobileMenu = (event) => {
    event.preventDefault();
    setMobileMenu(!mobileMenu);
  };

  useEffect(() => {
    new SimpleBar(dom(".side-nav .scrollable")[0]);
    setFormattedMenu(sideMenu());
  }, [url]);

  const handleLinkClick = (event, menu, pathname) => {

    event.preventDefault(); // Prevent the default link behavior.

    if (menu.subMenu) {
      menu.activeDropdown = !menu.activeDropdown;
    } else {
      router.get(pathname, {}, {replace:true})
      
      if (mobileMenu) {
        setMobileMenu(false);
      }
    }

    setFormattedMenu(toRaw(formattedMenu));
  }

  // Close mobile menu after clicking outside
  useEffect(() => {
    const handleClickOutside = (event) => {

      const clickedElement = event.target;
      // Check if the pseudo-element was clicked
      const isOverlay = window.getComputedStyle(clickedElement, "::before").content === '"\u200B"';

      if (isOverlay) {
        setMobileMenu(false); // Close the menu
      }
    };

    if (mobileMenu) {
      document.addEventListener("mousedown", handleClickOutside);
    } else {
      document.removeEventListener("mousedown", handleClickOutside);
    }

    return () => {
      document.removeEventListener("mousedown", handleClickOutside);
    };
  }, [mobileMenu]);

  return (

    <div className="flex flex-col h-full">
      <div className="flex flex-1">

        {/* BEGIN: Side Menu */}
        <nav
          className={classnames({
            "side-nav": true,
            "side-nav--active": mobileMenu,
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
              {formattedMenu.map((menu, menuKey) => {

                const hasAccess = userHasPathAccess(user, menu);

                // Skip rendering if no access.
                if (!hasAccess) return null

                return (
                  menu?.type === "title" ? (
                    <li className="side-nav__devider mb-4" key={menu + menuKey}>
                      {menu.title}
                    </li>
                  ) : (
                    <li key={menu + menuKey}>

                      <a
                        href={menu.subMenu ? "#" : menu.pathname}
                        className={classnames({
                          "side-menu": true,
                          "side-menu--active": menu.active,
                          "side-menu--open": menu.activeDropdown,
                        })}
                        onClick={(event) => handleLinkClick(event, menu, menu.pathname)}
                      >
                        <div className="side-menu__icon">
                          <Heroicon icon={menu.icon} />
                        </div>
                        <div className="side-menu__title">
                          {menu.title}
                          {menu.subMenu && (
                            <div
                              className={classnames({
                                "side-menu__sub-icon": true,
                                "transform rotate-180": menu.activeDropdown,
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
                            "side-menu__sub-open": menu.activeDropdown,
                          })}
                        >
                          {menu.subMenu.map((subMenu, subMenuKey) => {

                            const hasAccess = userHasPathAccess(user, subMenu);

                            // Skip rendering if no access.
                            if (!hasAccess) return null;

                            return (
                              <li key={subMenuKey}>
                                <a
                                  href={subMenu.subMenu ? "#" : subMenu.pathname}
                                  className={classnames({
                                    "side-menu": true,
                                    "side-menu--active": subMenu.active,
                                  })}
                                  onClick={(event) => handleLinkClick(event, subMenu, subMenu.pathname)}
                                >
                                  <div className="side-menu__icon">
                                    <Heroicon icon={subMenu.icon} />
                                  </div>
                                  <div className="side-menu__title">
                                    {subMenu.title}
                                    {subMenu.subMenu && (
                                      <div
                                        className={classnames({
                                          "side-menu__sub-icon": true,
                                          "transform rotate-180":
                                            subMenu.activeDropdown,
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
                                      "side-menu__sub-open":
                                        subMenu.activeDropdown,
                                    })}
                                  >
                                    {subMenu.subMenu.map(
                                      (lastSubMenu, lastSubMenuKey) => (
                                        <li key={lastSubMenuKey}>
                                          <a
                                            href={
                                              lastSubMenu.subMenu
                                                ? "#"
                                                : lastSubMenu.pathname
                                            }
                                            className={classnames({
                                              "side-menu": true,
                                              "side-menu--active":
                                                lastSubMenu.active,
                                            })}
                                            onClick={(event) => handleLinkClick(event, lastSubMenu, lastSubMenu.pathname)}
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
                )
              }
              )}
              {/* END: First Child */}
            </ul>
          </div>
        </nav>
        {/* END: Side Menu */}
        {/* BEGIN: Content */}
        <div
          className={classnames({
            wrapper: true,
          })}
        >
          <TopBar toggleMobileMenu={toggleMobileMenu} user={user} fontSize={fontSize} setFontSize={setFontSize} />
          <div className="content">
            {children}
          </div>
        </div>
        {/* END: Content */}
      </div>
      <Footer user={user} fontSize={fontSize} setFontSize={setFontSize} />
      <Toaster toastOptions={{}} position={isMobile ? 'bottom-center' : 'top-right'} richColors />
    </div>
  );
}


export default Layout;
