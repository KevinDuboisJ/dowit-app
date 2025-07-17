import { router, usePage } from '@inertiajs/react'
import { useState } from 'react'
import background from '@images/login-bg2.png'
import UserIcon from '@/components/svg/UserIcon'
import PasswordIcon from '@/components/svg/PasswordIcon'
import { Tooltip } from '@/components/tooltip'
import Logo from "@images/logo.png";

const Login = ({ users, errors }) => {

  const { flash } = usePage().props
  const [username, setUsername] = useState('')
  const [password, setPassword] = useState('')

  function handleSubmit(e) {
    e.preventDefault()
    router.post('/login', { username, password }, { only: ['errors', 'flash'] })
  }

  return (
    <div className={`flex items-center justify-center h-screen bg-cover bg-center before:absolute before:h-screen before:w-screen before:bg-white bg-white before:content-[""] before:opacity-20`}
      style={{ backgroundImage: `url(${background})` }}>
      <div className='flex flex-col p-5 justify-center items-center w-96 height: 100vh; overflow: auto;'>
        <img alt="Dowit" className="" src={Logo} width="w-auto" />

        <div className='w-full p-1 z-10 min-height: 100vh; display: flex; align-items: center; justify-content: center;'>
          <form className='flex flex-col' onSubmit={handleSubmit}>
            <h1 className='text-sm font-light leading-6'>Aanmelden<Tooltip name='loginUser' placement='bottom' /></h1>
            <h2 className='font-bold text-xl'> {import.meta.env.VITE_APP_NAME} </h2>

            <div className='flex flex-col space-y-4 mt-3'>
              <IconDataListInput value={username} Icon={UserIcon} type='text' placeholder='Gebruikersnaam' onChange={e => setUsername(e.target.value)} errors={errors} users={users} />
              <IconInput value={password} Icon={PasswordIcon} type='password' placeholder='Wachtwoord' onChange={e => setPassword(e.target.value)} errors={errors} />
              <button className='text-sm font-light p-3 border rounded-md bg-[#3e6da9] text-white' type='submit'>Aanmelden</button>
              {(flash.message || errors?.wrongCredentials) &&
                <i className='text-sm text-red-600 text-center'>
                  {flash.message || errors?.wrongCredentials}
                </i>
              }


            </div>
          </form>
        </div>
      </div>
    </div>
  );
}

const IconDataListInput = ({ name, value, type, placeholder, onChange, Icon, errors, users }) => {

  return (
    <div className='flex flex-row items-center relative h-9 w-full font-light border rounded-md bg-gray-50'>
      <Icon className='absolute left-2 z-10 w-3' errors={errors[name]} />
      <input className='h-full w-full h-6 p-2 ml-7 placeholder:text-sm rounded-md bg-gray-50
            rounded-tl-none rounded-bl-none border-0 border-l border-slate-200 focus:border-l focus:border-slate-200
            placeholder:text-gray-300' list='users-list' value={value} onChange={onChange} type={type} placeholder={placeholder} />
      <UserDatalist users={users} />

      {errors[name] && <i className='text-sm text-red-600'>{errors[name]}</i>}
    </div>
  )
}
const IconInput = ({ name, value, type, placeholder, onChange, Icon, errors }) => {

  return (
    <div className='flex flex-row items-center relative h-9 w-full font-light border rounded-md bg-gray-50'>

      <Icon className='absolute left-2 z-10 w-3' errors={errors[name]} />
      <input className='h-full w-full h-6 p-2 ml-7 placeholder:text-sm rounded-md bg-gray-50
            rounded-tl-none rounded-bl-none border-0 border-l border-slate-200 focus:border-l focus:border-slate-200
            placeholder:text-gray-300 '
        value={value}
        onChange={onChange}
        type={type}
        placeholder={placeholder}
      />


      {errors[name] && <i className='text-sm text-red-600'>{errors[name]}</i>}
    </div>
  )
}

const UserDatalist = ({ users }) => (
  <datalist id="users-list">
    {users.map((user, index) => (
      <option key={index} value={user.username} />
    ))}
  </datalist>
)

export default Login;