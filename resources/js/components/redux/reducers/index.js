import {combineReducers} from 'redux';
import register from './register';

const allReducers = combineReducers({
    register: register,
});

export default allReducers
