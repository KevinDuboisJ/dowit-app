import React from 'react';
import styles from './TooltipBox.module.scss';

const ModalOverlay = React.forwardRef(({ onClose, position, children }, ref) => {
  return (
    <div ref={ref} className={styles.tooltipModal} style={{ top: position.y, left: position.x }}>
      <a className={`${styles.close} text-[#474747]`} onClick={onClose}>
        <img src='/images/close.png' alt='Sluiten' title='Sluiten' />
      </a>
      <p className={"p-2"}>{children}</p>
    </div>
  );
});

const TooltipBox = React.forwardRef((props, ref) => {
  const closeModalHandler = () => {
    props.onTooltipClose();
  }
    return(
      <>    
        <ModalOverlay ref={ref} onClose={closeModalHandler} {...props}/>
      </>
    );
});

export default TooltipBox;
