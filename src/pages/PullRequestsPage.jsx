import React, { PureComponent } from 'react';
import CupcakeIpsum from '../modules/CupcakeIpsum.jsx';

export default class PullRequestsPage extends PureComponent {
  render() {
    return (
      <div style={{paddingBottom: '24px'}}>
        <h1>My awesome PRs</h1>
        <CupcakeIpsum paragraphs={5} />
      </div>
    );
  }
}
