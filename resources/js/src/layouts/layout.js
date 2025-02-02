import dom from "@left4code/tw-starter/dist/js/dom";

// Setup side menu
const findActiveMenu = (subMenu, location) => {
  let match = false;
  subMenu.forEach((item) => {
    if (
      ((location.forceActiveMenu !== undefined &&
        item.pathname === location.forceActiveMenu) ||
        (location.forceActiveMenu === undefined &&
          item.pathname === location.pathname)) &&
      !item.ignore
    ) {
      match = true;
    } else if (!match && item.subMenu) {
      match = findActiveMenu(item.subMenu, location);
    }
  });
  return match;
};

const nestedMenu = (menu, url) => {
  menu.forEach((item, key) => {
    if (typeof item !== "string") {
      let menuItem = menu[key];

      menuItem.active =
        ((url.forceActiveMenu !== undefined &&
          item.pathname === url.forceActiveMenu) ||
          (url.forceActiveMenu === undefined &&
            item.pathname === url) ||
          (item.subMenu && findActiveMenu(item.subMenu, url))) &&
        !item.ignore;

      if (item.subMenu) {
        menuItem.activeDropdown = findActiveMenu(item.subMenu, { pathname: url });
        menuItem = {
          ...item,
          ...nestedMenu(item.subMenu, url),
        };
      }
    }
  });

  return menu;
};

const enter = (el, done) => {
  dom(el).slideDown(300);
};

const leave = (el, done) => {
  dom(el).slideUp(300);
};

export { nestedMenu, enter, leave };
