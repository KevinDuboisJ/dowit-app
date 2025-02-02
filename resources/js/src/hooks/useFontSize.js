import { useState, useEffect } from "react";

export const useFontSize = (defaultSize = 16) => {
  
  const [fontSize, setFontSize] = useState(() => {
    const storedSize = localStorage.getItem("fontSize");
    return storedSize ? parseInt(storedSize, 10) : defaultSize;
  });

  useEffect(() => {
    localStorage.setItem("fontSize", fontSize); // Save font size to localStorage
    document.documentElement.style.setProperty("--font-base-size", `${fontSize}px`);
  }, [fontSize]);

  return { fontSize, setFontSize };
};