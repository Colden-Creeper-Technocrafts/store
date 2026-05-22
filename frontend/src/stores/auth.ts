import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

type AuthUser = {
  id?: number
  name?: string
  email?: string
  role?: string | null
}

const parseStoredUser = (): AuthUser | null => {
  const rawUser = localStorage.getItem('user')

  if (!rawUser) {
    return null
  }

  try {
    return JSON.parse(rawUser)
  } catch {
    localStorage.removeItem('user')
    return null
  }
}

const getRole = (user: AuthUser | null, fallbackRole?: string | null): string | null => {
  if (fallbackRole) {
    return fallbackRole
  }

  return user?.role ?? null
}

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(localStorage.getItem('token') || null)
  const user = ref<AuthUser | null>(parseStoredUser())

  const setUser = (newUser: AuthUser | null) => {
    user.value = newUser

    if (newUser) {
      localStorage.setItem('user', JSON.stringify(newUser))
      return
    }

    localStorage.removeItem('user')
  }

  const setToken = (newToken: string) => {
    token.value = newToken
    localStorage.setItem('token', newToken)
  }

  const setAuth = (payload: { token: string; user?: AuthUser | null; role?: string | null }) => {
    const resolvedRole = getRole(payload.user ?? null, payload.role)
    const authUser = payload.user
      ? {
          ...payload.user,
          role: resolvedRole
        }
      : null

    setToken(payload.token)
    setUser(authUser)
  }

  const role = computed(() => user.value?.role ?? null)
  const normalizedRole = computed(() => role.value?.toLowerCase() ?? null)
  const isAdmin = computed(() => normalizedRole.value === 'admin')
  const isCustomer = computed(() => normalizedRole.value === 'customer')

  const logout = () => {
    token.value = null
    localStorage.removeItem('token')
    setUser(null)
  }

  return {
    token,
    user,
    role,
    isAdmin,
    isCustomer,
    setUser,
    setToken,
    setAuth,
    logout
  }
})
