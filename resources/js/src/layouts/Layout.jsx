import { useState, useEffect, useMemo, useCallback } from 'react';
import { toRaw } from '@/utils';
import {
  sideMenu as useSideMenuStore,
} from '@/stores/side-menu';
import { nestedMenu } from './layout';
import { Toaster } from '@/base-components';
import classnames from 'classnames';
import TopBar from '@/components/top-bar/TopBar';
import { router, usePage } from '@inertiajs/react';
import Footer from '@/components/footer/Footer.jsx';
import { useFontSize, useIsMobile } from '@/hooks';
import { SideMenu } from './SideMenu';

const Layout = ({ children: { props: { user } }, children }) => {

  const { url } = usePage();
  const sideMenuStore = useMemo(() => useSideMenuStore, []);
  const [activeDropdowns, setActiveDropdowns] = useState({});
  const [mobileMenu, setMobileMenu] = useState(false);
  const { fontSize, setFontSize } = useFontSize(getComputedStyle(document.documentElement).getPropertyValue('--font-base-size')); // Default size in pixels
  const { isMobile } = useIsMobile();

  const sideMenuLinks = useMemo(() =>
    nestedMenu(sideMenuStore.menu, new URL(url, window.location.origin).pathname),
    [sideMenuStore.menu, url]
  );

  // Set active/inactive mobile menu
  const toggleMobileMenu = useCallback((event) => {
    event.preventDefault();
    setMobileMenu((prev) => !prev); // Use functional update to prevent stale state issues
  }, []);

  const handleLinkClick = useCallback((event, menu, pathname) => {
    event.preventDefault();

    // Check if the menu has a submenu
    if (menu.subMenu) {
      setActiveDropdowns((prevState) => ({
        ...prevState,
        [menu.title || pathname]: !prevState[menu.title || pathname], // Toggle activeDropdown
      }));
    } else {
      router.get(pathname, {}, { replace: true });
      if (mobileMenu) setMobileMenu(false);
    }
  }, [mobileMenu]);

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
        <SideMenu user={user} sideMenuLinks={sideMenuLinks} mobileMenu={mobileMenu} handleLinkClick={handleLinkClick} activeDropdowns={activeDropdowns} />
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
