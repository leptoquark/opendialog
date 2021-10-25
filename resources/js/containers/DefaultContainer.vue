<template>
  <div class="app">
    <div class="app-body">
      <Sidebar :navigationItems="navigationItems" :user="user" :minimized="false"/>
      <main :class="['main', {'main--loading': loading}]">
          <transition name="loader" mode="out-in">
            <router-view v-if="!loading"></router-view>
            <Loader v-else></Loader>
          </transition>
      </main>
    </div>
  </div>
</template>

<script>

export default {
  name: 'DefaultContainer',
  components: {},
  data() {
    return {
      loading: false
    }
  },
  created() {
    this.loading = this.$store.state.loading
    this.unsubscribe = this.$store.subscribe((mutation, state) => {
      if (mutation.type === 'toggleLoading') {
        this.loading = state.loading
      }
    })
  },
  beforeDestroy() {
    this.unsubscribe()
  },
  computed: {
    navigationItems () {
      return window.NavigationItems
    },
    user() {
      return {
        name: window.user.name,
        id: window.user.id,
        email: window.user.email,
        image: '/images/logo.svg'
      };
    }
  }
}
</script>

<style lang="scss" scoped>
.loader-enter {
  opacity: 0;
}
.loader-enter-active {
  transition: opacity 0.2s ease-in;
}
.loader-enter-to {
  opacity: 1;
}
.loader-leave {
  opacity: 1;
}
.loader-leave-active {
  transition: opacity 0.2s ease-out;
}
.loader-leave-to {
  opacity: 0;
}
</style>
