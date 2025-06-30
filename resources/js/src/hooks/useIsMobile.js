import { useState, useEffect, useRef } from "react";

export const useIsMobile = () => {
  const [isMobile, setIsMobile] = useState(window.innerWidth <= 1280);
  const hasTransitionedToMobile = useRef(false);

  useEffect(() => {
    function handleResize() {
      
      const currentlyMobile = window.innerWidth <= 1280;

      // Detect transition to mobile
      if (!isMobile && currentlyMobile) {
        hasTransitionedToMobile.current = true;
      }

      if (!isMobile && !currentlyMobile) {
        hasTransitionedToMobile.current = false;
      }

      setIsMobile(currentlyMobile);
    }

    window.addEventListener("resize", handleResize);

    return () => {
      window.removeEventListener("resize", handleResize);
    };
  }, []); // Empty dependency array ensures the event listener is added only once

  return { isMobile, hasTransitionedToMobile: hasTransitionedToMobile.current };

};