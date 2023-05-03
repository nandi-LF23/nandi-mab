<template>
    <div class='focus_menu' :class='expanded ? "expanded" : ""'>
        <div class='toggle' @click='expanded = !expanded'>
            <i class="arrow" :class='expanded ? "left" : "right"'></i>
        </div>

        <div class="focus_header">
            <div class="focus_header_inner">
                <slot name="header"></slot>
            </div>
        </div>

        <div class='focus_inner'>
            <div class='items_section'>
                <slot></slot>
            </div>
        </div>

        <div class="focus_footer">
            <div class="focus_footer_inner">
                <slot name="footer"></slot>
            </div>
        </div>

    </div>
</template>
<script>
import PerfectScrollbar from 'perfect-scrollbar';
import 'perfect-scrollbar/css/perfect-scrollbar.css';

export default {
    name: 'FocusMenu',
    data(){
        return {
            expanded: false,
            psb: null
        }
    },
    methods: {
        initScrollbar(){
            let isWindows = navigator.platform.startsWith('Win');
            if (isWindows) {
                this.psb = new PerfectScrollbar('.items_section');
            }
        },
        expandMenu(){
            this.expanded = true;
        },
        collapseMenu(){
            this.expanded = false;
        }
    },
    created(){

    },
    mounted(){
        this.initScrollbar()
    },
    watch: {
        expanded: function(val) {
            this.$emit('focusMenuChanged');
            if(val){
                this.$emit('focusMenuCollapsed');
                let body = document.querySelector('body');
                if(body.className.indexOf('focusmenu_expanded') === -1){
                    body.className += ' focusmenu_expanded';
                }
            } else {
                this.$emit('focusMenuExpanded');
                let body = document.querySelector('body');
                if(body.className.indexOf('focusmenu_expanded') !== -1){
                    body.className = body.className.replace(' focusmenu_expanded', '');
                }
            }
        }
    }
}
</script>
<style scoped>

    /* Structural */

    .focus_menu {
        position:relative;
        float:left;
        height:100vh;
        min-width:0px; /* For collapse transition */
        z-index:999;
        background-color:rgba(255,255,255,0.75);
        transition: all .25s ease-in-out;
    }

    .focus_menu:hover {
        background-color:rgba(255,255,255,1);
    }

    .focus_menu.expanded {
        min-width:250px;
    }

    .focus_menu .focus_header {
        position:relative;
        display:block;
        overflow:hidden;
        height:5rem;
        width:100%;
    }

    .focus_menu .focus_footer {
        position:relative;
        display:block;
        overflow:hidden;
        height:0rem;
        width:100%;
    }

    .focus_menu .focus_inner {
        position:relative;
        display:block;
        overflow:hidden;
        width:100%;
        height: calc(100% - 5rem); /* change to more if footer needs height too */
    }

    .focus_menu .focus_inner .items_section {
        position:absolute;
        left:0;
        top:0;
        width:100%;
        min-width:250px;
        height:100%;
        overflow:hidden;
    }

    .focus_menu .focus_footer .focus_footer_inner,
    .focus_menu .focus_header .focus_header_inner {
        position:absolute;
        top:0;
        left:0;
        width:100%;
        height:100%;
    }

    .focus_menu:hover .toggle {
        background-color:rgba(255,255,255,1);
        transition: all .25s ease-in-out;
    }

    /* Toggle Button */

    .focus_menu .toggle {
        position:absolute;
        top:2.3rem;
        right:-2rem;
        width:2rem;
        height:2rem;
        background-color:rgba(255,255,255,0.75);
        text-align: center;
        line-height:2rem;
        font-weight: bold;
        font-size:1rem;
        transition: all .25s ease-in-out;
        z-index:999;
        border-radius: 0 0.25em 0.25em 0;
    }

    .focus_menu .toggle:hover {
        cursor:pointer;
    }

    .focus_menu .items_section .menu_item {
        min-height:5rem;
    }

    .arrow {
        border: solid black;
        border-width: 0 3px 3px 0;
        display: inline-block;
        padding: 3px;
    }

    .arrow.right {
        transform: rotate(-45deg) translate(-22.5%, -22.5%);
        -webkit-transform: rotate(-45deg) translate(-22.5%, -22.5%);
    }

    .arrow.left {
        transform: rotate(135deg);
        -webkit-transform: rotate(135deg);
    }

</style>