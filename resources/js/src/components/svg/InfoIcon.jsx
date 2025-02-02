const InfoIcon = ({fill, stroke, customStyle}) => {
    return(
        <svg className={customStyle} xmlns='http://www.w3.org/2000/svg' width='44.16' height='43.69' viewBox='0 0 44.16 43.69'>
        <g id='Layer_2' data-name='Layer 2'>
            <g id='Layer_3' data-name='Layer 3'>
                <path d='M26.18,41.66a20,20,0,1,0-8.2,0' fill='none' stroke={stroke} strokeMiterlimit='10'
                    strokeWidth='4.16' />
                <path fill={fill} d='M19.74,42.39h4.75V21.18H19.74Zm2.34-30.45a2.59,2.59,0,0,0-2,.75,2.71,2.71,0,0,0-.74,2,2.69,2.69,0,0,0,.74,2,2.6,2.6,0,0,0,2,.76,2.6,2.6,0,0,0,2-.76,2.69,2.69,0,0,0,.74-2,2.71,2.71,0,0,0-.74-2A2.59,2.59,0,0,0,22.08,11.94Z' />
            </g>
        </g>
        </svg>
    )
}

export default InfoIcon