import React, { useState } from 'react';
import { Form, Input, Button, Checkbox } from 'antd';
import {addUser} from './redux/actions';
import {useSelector,useDispatch} from "react-redux";

function Register() {
    //const [email, setEmail] = useState('');
    //const [password, setPassword] = useState('');
    const dispatch = useDispatch();

    const onFinish = (values) => {
        console.log('Success:', values);
        dispatch(addUser(values));
    };

    const onFinishFailed = (errorInfo) => {
        console.log('Failed:', errorInfo);
    };


    return (
        <div className="col-lg-8 offset-lg-2">
            <h2>Register</h2>
            <Form
                {...layout}
                name="basic"
                initialValues={{
                    remember: true,
                }}
                onFinish={onFinish}
                onFinishFailed={onFinishFailed}
            >
                <Form.Item
                    label="Username"
                    name="username"
                    rules={[
                        {
                            required: true,
                            message: 'Please input your username!',
                        },
                    ]}
                    className='justify-content-center'
                >
                    <Input />
                </Form.Item>

                <Form.Item
                    label="Password"
                    name="password"
                    rules={[
                        {
                            required: true,
                            message: 'Please input your password!',
                        },
                    ]}
                    className='justify-content-center'
                >
                    <Input.Password />
                </Form.Item>


                <Form.Item {...tailLayout}>
                    <Button type="primary" htmlType="submit">
                        Submit
                    </Button>
                </Form.Item>
            </Form>
        </div>
    );
}





const layout = {
    labelCol: {
        span: 2,
    },
    wrapperCol: {
        span: 10,
    },
};
const tailLayout = {
    wrapperCol: {
        //offset: 8,
        span: 24,
    },
};
export default Register;
