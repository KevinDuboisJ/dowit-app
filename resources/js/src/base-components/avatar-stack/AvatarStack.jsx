import {
  Avatar,
  AvatarImage,
  AvatarFallback,
  Heroicon,
} from '@/base-components'
import { useState } from 'react'

export const AvatarStack = ({ users }) => {

  return (
    users.map((user) => (
      <Avatar key={user.id} className="w-6 h-6 inline-block mr-1">
        <AvatarImage src={user.image_path} alt={user.lastname} />
        <AvatarFallback>{user.lastname.charAt(0)}</AvatarFallback>
      </Avatar>
    ))
  )
}

export const AvatarStackRemovable = ({ avatars, onValueChange = () => { } }) => {

  const [users, setUsers] = useState(avatars);

  // const onUnassignment = async (userId) => {
  //   await handleAjaxRequest(
  //     () => axios.post(`/task/${task.id}/update`, {
  //       action: 'unassign',
  //       selectedUsers: [userId],
  //       updated_at: task.updated_at,
  //     }),
  //     (data) => {
  //       handleRowUpdate(data); // Success callback

  //     }
  //   );
  // };


  const handleValueChange = (value) => {
    setUsers(users.filter((user) => user.id !== value));
    onValueChange(value);
  }

  {/* <div className='flex items-center'>
                <div>
                  <h4 className="text-sm font-medium leading-none">Reeds toegewezen</h4>
                  <p className="text-xs text-muted-foreground">
                    Personen
                  </p>
                </div>
                <div className="flex space-x-2">
                  <AvatarStackRemovable users={task?.assigned_users} />
                </div>
                <Button type="submit" className='ml-auto'>Toewijzen</Button>
              </div> */
  }

  return (
    <div className='flex items-center space-x-1'>
      {users.map((user) => (
        <div key={user.id} className="relative">
          <Avatar>
            <AvatarImage src={user.image_path} alt={user.lastname} />
            <AvatarFallback>{user.lastname.charAt(0)}</AvatarFallback>
          </Avatar>
          {/* Delete Icon */}
          <Heroicon icon="XMark"
            onClick={() => handleValueChange(user.id)}
            className="h-4 w-4 absolute top-1 right-1 transform translate-x-1/2 -translate-y-1/2 cursor-pointer text-red-800"
            size={16}
          />
        </div>
      ))}
    </div>
  )

}

