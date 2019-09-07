import ProjectAPI from "../api/project";

const CREATING_PROJECT = "CREATING_PROJECT",
    CREATING_PROJECT_SUCCESS = "CREATING_PROJECT_SUCCESS",
    CREATING_PROJECT_ERROR = "CREATING_PROJECT_ERROR",
    FETCHING_PROJECTS = "FETCHING_PROJECTS",
    FETCHING_PROJECTS_SUCCESS = "FETCHING_PROJECTS_SUCCESS",
    FETCHING_PROJECTS_ERROR = "FETCHING_PROJECTS_ERROR",
    FETCHING_PROJECT = "FETCHING_PROJECT",
    FETCHING_PROJECT_SUCCESS = "FETCHING_PROJECT_SUCCESS",
    FETCHING_PROJECT_ERROR = "FETCHING_PROJECTSERROR";

export default {
    namespaced: true,
    state: {
        isLoading: false,
        error: null,
        projects: [],
        project:null
    },
    getters: {
        isLoading(state) {
            return state.isLoading;
        },
        hasError(state) {
            return state.error !== null;
        },
        error(state) {
            return state.error;
        },
        hasProjects(state) {
            return state.projects.length > 0;
        },
        projects(state) {
            return state.projects;
        },
        project(state) {
            return state.project;
        }
    },
    mutations: {
        [CREATING_PROJECT](state) {
            state.isLoading = true;
            state.error = null;
        },
        [CREATING_PROJECT_SUCCESS](state, project) {
            state.isLoading = false;
            state.error = null;
            state.projects.unshift(project);
        },
        [CREATING_PROJECT_ERROR](state, error) {
            state.isLoading = false;
            state.error = error;
            state.projects = [];
        },
        [FETCHING_PROJECTS](state) {
            state.isLoading = true;
            state.error = null;
            state.projects = [];
        },
        [FETCHING_PROJECTS_SUCCESS](state, projects) {
            state.isLoading = false;
            state.error = null;
            state.projects = projects;
        },
        [FETCHING_PROJECTS_ERROR](state, error) {
            state.isLoading = false;
            state.error = error;
            state.projects = [];
        },
        [FETCHING_PROJECT](state) {
            state.isLoading = true;
            state.error = null;
            state.project = [];
        },
        [FETCHING_PROJECT_SUCCESS](state, project) {
            state.isLoading = false;
            state.error = null;
            state.project = project;
        },
        [FETCHING_PROJECT_ERROR](state, error) {
            state.isLoading = false;
            state.error = error;
            state.project = [];
        }
    },
    actions: {
        async create({ commit }, name) {
            commit(CREATING_PROJECT);
            try {
                let response = await ProjectAPI.create(name);
                commit(CREATING_PROJECT_SUCCESS, response.data);
                return response.data;
            } catch (error) {
                commit(CREATING_PROJECT_ERROR, error);
                return null;
            }
        },
        async findAll({ commit }) {
            commit(FETCHING_PROJECTS);
            try {
                let response = await ProjectAPI.findAll();
                commit(FETCHING_PROJECTS_SUCCESS, response.data);
                return response.data;
            } catch (error) {
                commit(FETCHING_PROJECTS_ERROR, error);
                return null;
            }
        },
        async find({ commit }, id) {
            commit(FETCHING_PROJECT);
            try {
                let response = await ProjectAPI.find(id);
                commit(FETCHING_PROJECT_SUCCESS, response.data);
                return response.data;
            } catch (error) {
                commit(FETCHING_PROJECT_ERROR, error);
                return null;
            }
        }
    }
};