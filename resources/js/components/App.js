import React from 'react';
import ReactDOM from 'react-dom';
import 'antd/dist/antd.css';
import {createStore} from 'redux';
import allReducers from './redux/reducers';
import { Provider } from 'react-redux'
import LayoutArea from "./LayoutArea";

const store = createStore(allReducers,
    window.__REDUX_DEVTOOLS_EXTENSION__ && window.__REDUX_DEVTOOLS_EXTENSION__()
);

function Example() {
    return (
        <Provider store={store}>
            <LayoutArea />
        </Provider>
    );
}

export default Example;

if (document.getElementById('root')) {
    ReactDOM.render(<Example />, document.getElementById('root'));
}
