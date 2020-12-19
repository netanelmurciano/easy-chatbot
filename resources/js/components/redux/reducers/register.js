
const register = (state = [], action) => {

    switch (action.type) {
        case 'ADD_USER':
            state = action.payload;
            console.log('ADD_USER');
            console.log(action.payload);
            return state;

    }
    return state;
};
export default register;
