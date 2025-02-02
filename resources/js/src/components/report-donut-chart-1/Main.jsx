import { Chart } from "@/base-components";
import { colors } from "@/lib";
import PropTypes from "prop-types";
import { useRecoilValue } from "recoil";
import { colorScheme as colorSchemeStore } from "@/stores/color-scheme";
import { darkMode as darkModeStore } from "@/stores/dark-mode";
import { useMemo } from "react";

function Main(props) {
  const darkMode = useRecoilValue(darkModeStore);
  const colorScheme = useRecoilValue(colorSchemeStore);

  const data = useMemo(() => {
    const configData = {
      labels: ["17 - 30 Years old", "31 - 50 Years old", ">= 50 Years old"],
      datasets: [
        {
          data: [15, 10, 65],
          backgroundColor: colorScheme
            ? [colors.pending(0.5), colors.warning(0.5), colors.primary(0.5)]
            : "",
          hoverBackgroundColor: colorScheme
            ? [colors.pending(0.5), colors.warning(0.5), colors.primary(0.5)]
            : "",
          borderWidth: 1,
          borderColor: colorScheme
            ? [colors.pending(0.75), colors.warning(0.9), colors.primary(0.5)]
            : "",
        },
      ],
    };

    return darkMode ? configData : configData;
  });

  const options = useMemo(() => {
    return {
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false,
        },
      },
      cutout: "90%",
    };
  });

  return (
    <Chart
      type="doughnut"
      width={props.width}
      height={props.height}
      data={data}
      options={options}
      className={props.className}
    />
  );
}

Main.propTypes = {
  width: PropTypes.oneOfType([PropTypes.number, PropTypes.string]),
  height: PropTypes.oneOfType([PropTypes.number, PropTypes.string]),
  className: PropTypes.string,
};

Main.defaultProps = {
  width: "auto",
  height: "auto",
  className: "",
};

export default Main;
