import { memo } from 'react';
import { Heroicon } from '@/base-components';
import PropTypes from 'prop-types';
import { UserMenu } from '@/components';

function TopBar({user, toggleMobileMenu, fontSize, setFontSize}) {

  return (
    <>
      {/* BEGIN: Top Bar */}
      <div className="top-bar">
        {/* BEGIN: Mobile Menu */}
        <div className="flex justify-between w-full fadeInRight xl:hidden mr-3 sm:mr-6">
          <div
            className="mobile-menu-toggler cursor-pointer border-none"
            onClick={toggleMobileMenu}
          >
            <Heroicon icon="Bars3" className="mobile-menu-toggler__icon transform dark:text-slate-500" />
          </div>
          <UserMenu user={user} fontSize={fontSize} setFontSize={setFontSize}/>
        </div>
        {/* END: Mobile Menu */}
      </div>
      {/* END: Top Bar */}
    </>
  );
}

TopBar.propTypes = {
  toggleMobileMenu: PropTypes.func,
};

export default memo(TopBar);
