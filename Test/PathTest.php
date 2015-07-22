<?php
/**
 * Created by IntelliJ IDEA.
 * User: andri
 * Date: 7/1/15
 * Time: 6:54 PM
 */

namespace Rotor\Test;

use Rotor\Exception\InvalidPathOperationException;
use Rotor\Path;

class PathTest extends \PHPUnit_Framework_TestCase
{
    const WEB_ROOT = '/var/www/';

    protected function setUp(){
        $_SERVER['DOCUMENT_ROOT'] = static::WEB_ROOT;
    }

    public function test_creates_a_Path_object_via_static_call()
    {
        $this->assertInstanceOf('Rotor\Path', Path::Create(''));
    }

    public function test_creates_a_Path_object_via_constructor()
    {
        $this->assertInstanceOf('Rotor\Path', new Path(''));
    }

    public function test_is_neutral_to_valid_paths()
    {
        $this->assertEquals('/foo/', (string)Path::Create('/foo/'));
        $this->assertEquals('foo/', (string)Path::Create('foo/'));
        $this->assertEquals('/foo', (string)Path::Create('/foo'));
    }

    public function test_detects_a_trailing_slash_as_a_directory()
    {
        $this->assertTrue(Path::Create('/')->isDirectory());
        $this->assertTrue(Path::Create('/var/test/')->isDirectory());
        $this->assertTrue(Path::Create('notAbsolute/test/')->isDirectory());
    }

    public function test_interprets_lack_of_trailing_slash_as_not_a_directory()
    {
        $this->assertFalse(Path::Create('/var/test')->isDirectory());
    }

    public function test_interprets_as_directory_when_provided_is_directory_flag()
    {
        $this->assertTrue(Path::CreateDir('/var/test')->isDirectory());
        $this->assertTrue(Path::CreateDir('/var/test/')->isDirectory());
    }

    public function test_treats_empty_strings_as_directories(){
        $this->assertTrue(Path::Create('')->isDirectory());
    }

    public function test_interprets_as_directory_when_created_with_CreateDir_static_call()
    {
        $this->assertTrue(Path::CreateDir('/var/test')->isDirectory());
        $this->assertTrue(Path::CreateDir('/var/test/')->isDirectory());
    }

    public function test_compacts_a_path_with_multiple_consecutive_slashes()
    {
        $this->assertEquals('/path/to/somewhere/', (string)Path::Create('/path//to/somewhere/'));
        $this->assertEquals('/path/to/somewhere/', (string)Path::Create('/path//to////somewhere/'));
        $this->assertEquals('/', (string)Path::Create('///'));
    }

    public function test_functions_as_realpath_but_with_non_existing_paths()
    {
        $this->assertEquals('/test/', (string)Path::Create('/var/../test/'));
        $this->assertEquals('/var/test/', (string)Path::Create('/var/./test/'));
        $this->assertEquals('/', (string)Path::Create('/var/./test/.././././../'));
    }

    public function test_interprets_no_ending_slash_as_file()
    {
        $this->assertTrue(Path::Create('/var/test/file.ext')->isFile());
        $this->assertTrue(Path::Create('/var/something')->isFile());
        $this->assertTrue(Path::Create('filename')->isFile());
        $this->assertTrue(Path::Create('filename.ext')->isFile());
        $this->assertTrue(Path::Create('/filename.ext')->isFile());
        $this->assertTrue(Path::Create('/path/to/file')->isFile());
    }

    public function test_interprets_leading_slash_as_absolute()
    {
        $this->assertTrue(Path::Create('/')->isAbsolute());
        $this->assertTrue(Path::Create('/path/should/be/absolute')->isAbsolute());
    }

    public function test_provides_filename_for_valid_file_paths()
    {
        $this->assertEquals('file', Path::Create('/path/to/file')->filename());
        $this->assertEquals('file', Path::Create('path/to/file.ext')->filename());
        $this->assertEquals('file.nonext', Path::Create('path/to/file.nonext.ext')->filename());
        $this->assertEquals('.filename', Path::Create('path/to/.filename')->filename());
        $this->assertEquals('.filename', Path::Create('path/to/.filename.ext')->filename());
    }

    public function test_provides_extension_for_valid_file_paths()
    {
        $this->assertEquals('', Path::Create('path/to/file')->extension());
        $this->assertEquals('ext', Path::Create('path/to/file.ext')->extension());
        $this->assertEquals('ext', Path::Create('path/to/file.nonext.ext')->extension());
        $this->assertEquals('', Path::Create('path/to/.filename')->extension());
        $this->assertEquals('ext', Path::Create('path/to/.filename.ext')->extension());
    }

    public function test_provides_basename_for_valid_file_paths()
    {
        $this->assertEquals('file', Path::Create('path/to/file')->basename());
        $this->assertEquals('file.ext', Path::Create('path/to/file.ext')->basename());
        $this->assertEquals('file.nonext.ext', Path::Create('path/to/file.nonext.ext')->basename());
        $this->assertEquals('.filename', Path::Create('path/to/.filename')->basename());
        $this->assertEquals('.filename.ext', Path::Create('path/to/.filename.ext')->basename());
    }

    public function test_changes_filename()
    {
        $this->assertEquals('/path/to/newFile.ext', (string)Path::Create('/path/to/file.ext')->filename('newFile'));
    }

    public function test_changes_extension()
    {
        $this->assertEquals('/path/to/file.xxx', (string)Path::Create('/path/to/file.ext')->extension('xxx'));
    }

    public function test_changes_basename()
    {
        $this->assertEquals('/path/to/filename.xxx', (string)Path::Create('/path/to/file.ext')->basename('filename.xxx'));
    }

    public function test_changes_directory(){
        $this->assertEquals('/new/dir/file.ext',(string)Path::Create('/path/to/file.ext')->directory('/new/dir'));
    }

    public function test_appends_two_directories(){
        $this->assertEquals('/foo/path/bar/path',(string)Path::Create('/foo/path/')->append('/bar/path'));
        $this->assertEquals('foo/path/bar/path',(string)Path::Create('foo/path/')->append('/bar/path'));
        $this->assertEquals('/path',(string)Path::Create('/')->append('path'));
        $this->assertEquals('/path/',(string)Path::Create('/')->append('path/'));
        $this->assertEquals('/path/',(string)Path::Create('/')->append('path/'));
        $this->assertEquals('/path/',(string)Path::Create('')->append('/path/'));
    }

    public function test_appends_a_directory_and_a_file_path(){
        $this->assertEquals('/path/to/filename.ext',Path::Create('/path/to/')->append('filename.ext'));
    }


    public function test_throws_InvalidPathOperationException_when_appending_any_path_to_a_file_path(){
        try{
            Path::Create('/path/to/filename.ext')->append('/something/else/');
        } catch (InvalidPathOperationException $e){
            return;
        }
        $this->fail('Expected exception not returned');
    }

    public function test_detects_if_path_is_inside_web_root(){
        $this->assertTrue(Path::Create(static::WEB_ROOT.'foo/bar')->inWebRoot());

        $this->assertFalse(Path::Create('/not/in/web/root')->inWebRoot());
    }

    public function test_returns_url_if_path_in_web_root(){
        $this->assertEquals('/in/web/root',(string)Path::Create(static::WEB_ROOT.'/in/web/root')->webPath());
        $this->assertEquals('/',(string)Path::Create(static::WEB_ROOT)->webPath());
        $this->assertEquals('/file.ext',(string)Path::Create(static::WEB_ROOT.'/file.ext')->webPath());
    }
}
