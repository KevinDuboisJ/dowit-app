
export const FontSizeSlider = ({fontSize, setFontSize}) => {

  const handleChange = (event) => {
    setFontSize(event.target.value);
  };

  return (
    <div className="flex items-center gap-2">
      <span className="text-sm font-bold">A</span>
      <div className="flex items-center relative w-full">
        <div
          className="absolute top-1/2 left-0 h-1 bg-blue-500 rounded-lg pointer-events-none"
          style={{
            width: `${((fontSize - 12) / (26 - 12)) * 100}%`,
            transform: 'translateY(-50%)',
            zIndex: 0,
          }}
        ></div>
        <input
          type="range"
          min="12"
          max="26"
          value={fontSize}
          onChange={handleChange}
          className="relative z-10 w-full h-1 bg-transparent appearance-none cursor-pointer focus:outline-none focus:ring-0 focus:border-transparent"
        />
      </div>
      <span className="text-lg font-bold">A</span>
    </div>

  );
};
