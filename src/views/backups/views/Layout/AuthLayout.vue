<template>
  <div>
    <div class="main-content">
      <zoom-center-transition
        :duration="pageTransitionDuration"
        mode="out-in"
      >
        <router-view></router-view>
      </zoom-center-transition>
    </div>

    <footer class="py-5" id="footer-main">
      <b-container >
        <b-row align-v="center" class="justify-content-xl-between">
          <b-col xl="12">
            <div class="copyright text-center text-muted">
              © {{year}} LiquidFibre
            </div>
            <div class='build text-center'>Build: {{ mab_bn }}</div>
          </b-col>
        </b-row>
      </b-container>
    </footer>
  </div>
</template>
<script>
  import { BaseNav } from '@/components';
  import { ZoomCenterTransition } from 'vue2-transitions';
  import mab_build_number from '../../mab_bn';

  export default {
    components: {
      BaseNav,
      ZoomCenterTransition
    },
    props: {
      backgroundColor: {
        type: String,
        default: 'black'
      }
    },
    data() {
      return {
        showMenu: false,
        menuTransitionDuration: 250,
        pageTransitionDuration: 200,
        year: new Date().getFullYear(),
        pageClass: 'login-page',
        mab_bn: mab_build_number
      };
    },
    computed: {
      title() {
        return `${this.$route.name} Page`;
      }
    },
    methods: {
      toggleNavbar() {
        document.body.classList.toggle('nav-open');
        this.showMenu = !this.showMenu;
      },
      closeMenu() {
        document.body.classList.remove('nav-open');
        this.showMenu = false;
      }
    },
    beforeRouteUpdate(to, from, next) {
      // Close the mobile menu first then transition to next page
      if (this.showMenu) {
        this.closeMenu();
        setTimeout(() => {
          next();
        }, this.menuTransitionDuration);
      } else {
        next();
      }
    }
  };
</script>
<style lang="scss">
  $scaleSize: 0.8;
  @keyframes zoomIn8 {
    from {
      opacity: 0;
      transform: scale3d($scaleSize, $scaleSize, $scaleSize);
    }
    100% {
      opacity: 1;
    }
  }

  .main-content .zoomIn {
    animation-name: zoomIn8;
  }

  @keyframes zoomOut8 {
    from {
      opacity: 1;
    }
    to {
      opacity: 0;
      transform: scale3d($scaleSize, $scaleSize, $scaleSize);
    }
  }

  .main-content .zoomOut {
    animation-name: zoomOut8;
  }

  .separator-skew {
    height:80px;
  }

  /* credits: https://codepen.io/claviska */
  .mab_spinner {
    /* Spinner size and color */
    width: 1.5rem;
    height: 1.5rem;
    border-top-color: #444;
    border-left-color: #444;

    /* Additional spinner styles */
    animation: mab_spinner 400ms linear infinite;
    border-bottom-color: transparent;
    border-right-color: transparent;
    border-style: solid;
    border-width: 2px;
    border-radius: 50%;  
    box-sizing: border-box;
    display: inline-block;
    vertical-align: middle;
  }

  /* Animation styles */
  @keyframes mab_spinner {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }

  .mab_spinner.center {
    position:absolute;
    top:50%;
    left:50%;
    transform:translate(-50%, -50%);
  }

  .mab_spinner.right {
    position:absolute;
    top:0;
    right:1rem;
  }

  .mab_spinner.small {
    width: 0.8rem !important;
    height: 0.8rem !important;
  }

  .mab_spinner.light {
    border-top-color: #fff;
    border-left-color: #fff;
  }

  .build {
    color:#eee;
  }

</style>
