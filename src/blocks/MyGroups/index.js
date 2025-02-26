import { render } from '@wordpress/element';
import { BrowserRouter} from 'react-router-dom';
import MyGroups from './MyGroups';



// Render the App component into the DOM
render(<BrowserRouter><MyGroups /></BrowserRouter>, document.getElementById('moowoodle-my-group'));