# About

Hints for wrapping the webapp in an Android app.

Daniel Zimmel <zimmel@coll.mpg.de> 2014.

# Apache Cordova: Setup

Notes for Linux (Debian/Ubuntu) and Cordova 3.4

##Install Java & Ant

apt-get ...

## compile NodeJS

				sudo apt-get update
				sudo apt-get install g++ curl libssl-dev

				wget http://nodejs.org/dist/node-latest.tar.gz
 				tar -xzvf node-latest.tar.gz
 				cd node-v0.10.24
				./configure
 				make
 				sudo make install

## install Cordova with NodeJS

	 sudo npm install -g cordova

[Cordova Command Line Interface Docs](http://cordova.apache.org/docs/en/3.4.0/guide_cli_index.md.html#The%20Command-Line%20Interface)

## install latest Android SDK

[https://developer.android.com/sdk/index.html#download]

unzip to *~/android-development/adt-bundle/*

## setup PATH in .bashrc

write to .bashrc:
	 
	   export PATH=${PATH}:~/android-development/adt-bundle/sdk/platform-tools:~/android-development/adt-bundle/sdk/tools


will be active on new bash session, or do ``source ~/.bashrc``

## Setup Android SDK

start Eclipse in the ADT directory:

			./eclipse

			(Workspace: e.g. "android-development")

go to Window->Android SDK-Manager->Tools->Manage AVDs

create new AVD emulator for testing purposes (e.g. select Nexus 7 in AVD Device Manager)

(TODO: test Genymotion Android Emulator --> much faster?)

# Apache Cordova: start new Project

go to your Android workspace and:

	cordova create hello com.example.hello HelloWorld	
	cordova platform add android

## Get Cordova inappbrowser plugin
	
	$ cordova plugin add https://git-wip-us.apache.org/repos/asf/cordova-plugin-inappbrowser.git
	$ cordova plugin rm org.apache.cordova.core.inappbrowser

## Insert your HTML start page

see below for sample www/index.html redirect to webapp

## Build 

	cordova build 

APK is built in subdirectory.

Test your app in emulator:

		 cordova emulate android

Test your app in device (plugin to USB):

		 cordova run android

# Example www/index.html


		<!DOCTYPE html>
		<!--
				Licensed to the Apache Software Foundation (ASF) under one
				or more contributor license agreements.  See the NOTICE file
				distributed with this work for additional information
				regarding copyright ownership.  The ASF licenses this file
				to you under the Apache License, Version 2.0 (the
				"License"); you may not use this file except in compliance
				with the License.  You may obtain a copy of the License at
				
				http://www.apache.org/licenses/LICENSE-2.0
				
				Unless required by applicable law or agreed to in writing,
				software distributed under the License is distributed on an
				"AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
				KIND, either express or implied.  See the License for the
				specific language governing permissions and limitations
				under the License.
			-->
		<html>
			<head>
				<meta charset="utf-8" /> <meta name="format-detection" content="telephone=no" /> 
				<!-- WARNING: for iOS 7, remove the
						 width=device-width and height=device-height attributes. See
						 https://issues.apache.org/jira/browse/CB-4323 -->
				<meta name="viewport" content="user-scalable=no, initial-scale=1, maximum-scale=1, minimum-scale=1,
																			 width=device-width, height=device-height,
																			 target-densitydpi=device-dpi" /> 
				<link rel="stylesheet" type="text/css" href="css/index.css" />
				<title>Hello World</title>
			</head>
			<body>
				<script type="text/javascript">


					document.addEventListener("deviceready", onDeviceReady, false);
					function onDeviceReady() { // Now safe to use the Codova API


					var ref = window.open('https://www.mylibrary.domain/testurl',
					'_blank', 'location=no'); // ref.addEventListener('loadstart',
					function() { alert(event.url); }); }
				</script>
				<div class="app">
					<h1>Apache Cordova</h1>
					<div id="deviceready" class="blink">
						<p class="event listening">Connecting to Device</p>
						<p class="event received">Device is Ready</p>

					</div>
				</div>

				<script src="http://code.jquery.com/jquery-1.9.1.min.js"></script>
				<script type="text/javascript" src="cordova.js"></script>
				<script type="text/javascript" src="js/index.js"></script>
				<script type="text/javascript">
					app.initialize();
				</script>

			</body>
		</html>



Add icons/splash screen:
see *platforms/android/res* and Cordova Docs.

# Links

[Linux SDK setup](http://wiki.ubuntuusers.de/Android_SDK)

[Cordova CLI Docs](http://cordova.apache.org/docs/en/3.4.0/guide_cli_index.md.html)
