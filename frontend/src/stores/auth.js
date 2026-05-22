import { defineStore } from 'pinia'

const parseStoredUser = () => {
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

export const useAuthStore = defineStore('auth', {

    state: () => ({
        token: localStorage.getItem('token') || null,
        user: parseStoredUser()
    }),

    getters: {
        role: (state) => state.user?.role || null,
        isAdmin: (state) => (state.user?.role || '').toLowerCase() === 'admin',
        isCustomer: (state) => (state.user?.role || '').toLowerCase() === 'customer'
    },

    actions: {

        setUser(user) {
            this.user = user

            if (user) {
                localStorage.setItem('user', JSON.stringify(user))
                return
            }

            localStorage.removeItem('user')
        },

        setToken(token) {

            this.token = token

            localStorage.setItem('token', token)

        },

        setAuth({ token, user, role }) {
            const authUser = user
                ? {
                    ...user,
                    role: role || user.role || null
                }
                : null

            this.setToken(token)
            this.setUser(authUser)
        },

        logout() {

            this.token = null

            localStorage.removeItem('token')
            this.setUser(null)

        }

    }

})
