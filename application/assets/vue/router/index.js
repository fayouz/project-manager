import Vue from "vue";
import VueRouter from "vue-router";
import Home from "../views/Home";
import Projects from "../views/Projects";
import Project from "../views/Project";

Vue.use(VueRouter);

export default new VueRouter({
    mode: "history",
    routes: [
        { path: "/home", component: Home },
        { path: "/projects", component: Projects },
        { path: "/projects/:id", component: Project },
        { path: "*", redirect: "/home" }
    ]
});