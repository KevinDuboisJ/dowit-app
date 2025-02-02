const PasswordIcon = ({className, errors}) => {
    return(
    <svg className={className} xmlns="http://www.w3.org/2000/svg" width="40" height="47.69" viewBox="0 0 40 47.69" fill={errors?'#DC2626':''}>
    <g id="Layer_2" data-name="Layer 2">
        <g id="Layer_3" data-name="Layer 3">
            <path d="M20,0a12.36,12.36,0,0,0-9.89,5.19,15.26,15.26,0,0,0-2.94,9.09v7.43H0v26H40v-26H32.83V14.28C32.83,6.41,27.07,0,20,0Zm2.75,40.87h-5.5l1.46-6.38a3.46,3.46,0,0,1-1.78-3.1A3.26,3.26,0,0,1,20,28a3.26,3.26,0,0,1,3.08,3.42,3.47,3.47,0,0,1-1.79,3.1Zm6.08-19.16H11.16V14.28A10.54,10.54,0,0,1,13,8.36a8.57,8.57,0,0,1,7-3.91c4.87,0,8.83,4.41,8.83,9.83Z" 
            opacity={errors?'0.7':'0.2'}/>
        </g>
    </g>
</svg>
    )
}

export default PasswordIcon