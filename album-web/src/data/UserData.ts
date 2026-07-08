export interface UserData {
  id: string
  username: string
  nickname: string
  avatarUrl: string
  role: 'owner' | 'visitor' | 'collaborator'
  isLoggedIn: boolean
  createdAt: string
  updatedAt: string
}