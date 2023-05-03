<template>
  <router-view></router-view>
</template>

<script>
export default {
}
</script>
<style>
  .sidenav-toggler-line {
    background-color:#00A04C !important;
  }
  .thead-light th .cell {
    font-weight: bold;
    color:black;
  }

  .el-table__row td:first-child .cell,
  .el-table__row td:first-child a {
    font-weight:bold;
  }

  .header {
    background-image:url('/img/exe/farmlands.jpg');
    background-repeat:no-repeat;
    background-size:cover;
    background-position:center;
  }

  .header::before {
    position: absolute;
    top: 0;
    content: "";
    width: 100%;
    height: 100%;
    display: block;
    background-color: rgba(0,160,75,0.2);
  }

  .b-toast { max-width: 100%; }
  .b-toast .toast-header, .b-toast .toast-body { padding:0.25em; }

  .navbar-brand {
    width:100%;
    text-align:center;
  }

  /* .custom-control.custom-checkbox .custom-control-label::before,
  .custom-control.custom-checkbox .custom-control-label::after {
    top:0 !important;
  } */

  .navbar-vertical .navbar-nav .nav-link {
    padding: .475rem 1.5rem;
  }

  .map .mapboxgl-popup {
    max-width: 50% !important;
  }

  @media(max-width:1023px){
    .map .mapboxgl-popup {
      max-width:80% !important;
    }
  }

</style>