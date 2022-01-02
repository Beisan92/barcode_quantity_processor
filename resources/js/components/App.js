import axios from 'axios';
import React,{Component} from 'react'; 
import ReactDOM from 'react-dom';

class App extends Component { 

    state = { 
      selectedFile: null,
      validFileExtensions:  [".csv", ".xls", ".xlsx"],
      invalidTypeMsg: '',
      file: '',
      className: 'info',
      disableUpload: true,
      processing: false,
      updatedRecords: -1
    }; 
     
    onFileChange = event => { 
      var blnValid = false;

      for (var j = 0; j < this.state.validFileExtensions.length; j++) {
        var sCurExtension = this.state.validFileExtensions[j];
        if (event.target.files[0].name.substr(event.target.files[0].name.length - sCurExtension.length, sCurExtension.length).toLowerCase() == sCurExtension.toLowerCase()) {
            blnValid = true;
            break;
        }
      }
      this.setState({className: "info"});

      if (!blnValid) {
            this.setState({
              invalidTypeMsg: "Sorry, " + event.target.files[0].name + " is invalid, allowed extensions are: " 
              + this.state.validFileExtensions.join(", "),
              selectedFile: null,
              className: "error",
              disableUpload: true,
              updatedRecords: -1
            });
      } else {
          this.setState({invalidTypeMsg: "",
          selectedFile: event.target.files[0],
          disableUpload: false,
          updatedRecords: -1
        });
      }

      
    }; 
     
    onFileUpload = () => {
      this.setState({disableUpload: true,
        processing: true
      });
      
      if (this.state.selectedFile != null) {
        var formData = new FormData();
        formData.append("file", this.state.selectedFile);
        axios.post('api/upload', formData, {
            headers: {
              'Content-Type': 'multipart/form-data'
            }
        }).then((response) => {
          if (response.data.message != null) {
            if (response.data.success) {
              this.setState({className: "warning"});
            } else {
              this.setState({className: "error"});
            }
            this.setState({invalidTypeMsg: response.data.message});
          } else {
            this.setState({className: "info",
             invalidTypeMsg: "The file has been processed successfully"
            });
          }
          this.setState({disableUpload: false,
            processing: false,
            updatedRecords: response.data.updated});
        }, (error) => {
          this.setState({className: "error",
          disableUpload: false,
          processing: false});
        });  
      }
    }; 
     
    render() { 
      return (
        <div className="main-content">
         {this.state.processing && 
           <div className="overlay">
             <img className="loading-image" src="../images/loading.gif"/>
           </div>
         }
           <h1 className="header">Process Barcode CSV File</h1> 
          <div className="form-container">
              <div className="note">Choose file to process <span className="supported-format">(.csv, .xls, .xlsx)</span></div> 
              <div className="upload-form"> 
                  <input className="file-input" type="file" onChange={this.onFileChange} accept=".xlsx, .xls, .csv"/> 
                  <button className="upload-button" onClick={this.onFileUpload} disabled={this.state.disableUpload}> 
                    Upload
                  </button> 
              </div> 
            <div className={this.state.className}>{this.state.invalidTypeMsg}
              { this.state.updatedRecords >= 0 &&
                <div className="info"> {this.state.updatedRecords} records have been added or updated</div>
              }
            </div>
          </div>
        </div> 
      ); 
    } 
  } 

export default App;

if (document.getElementById('app')) {
    ReactDOM.render(<App />, document.getElementById('app'));
}