import React from 'react';

const Button = React.forwardRef(({type, children, onClick, customClass}, ref) => {

    customClass = customClass?customClass.join(' '):'';
    return(
        <button className={'p-2 bg-logoGreen font-light text-white rounded text-sm4 ' + customClass} ref={ref} type={type} onClick={onClick}>
            {children}
        </button>
    )
})

export default Button;