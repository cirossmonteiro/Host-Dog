import sys, os, shutil

thedir = "/var/www/"+sys.argv[1]
delete = []

fh = open(thedir+"/log.txt",'w')
fh.write("started")
fh.close()

def fix_name(name):
	#if ('/' in name):
	#	name = name.split('/')[-1]
	newn = ""
	for a in name:
		if a == ' ':
			newn += '_'
		elif ord(a) >= 128:
			newn += 'X'
		else:
			newn += a
	return newn

def read_file(file):
	global delete
		
	# insert code for analysing file
	tags = ['<script>','<?']
	ignore = ['doc','pdf','ppt']
	for a in ignore:
		if '.'+a in file:
			return 0;
	fh = open(file,'r')
	content = fh.read()
	fh.close()
	
	# analysing content
	for t in tags:
		if t in content:
			delete.append(file)
			return 1
	return 0
	

def read_dir(way):
	way2 = fix_name(way)
	if (way != way2):
		os.rename(way, way2)	
		way = way2
	way += '/'
		
	global delete
	list_of_files = os.listdir(way)
	for e in list_of_files:
		if os.path.isdir(way+e):
			print way+e+ " eh diretorio"
			read_dir(way+e)
		else:
			print way+e+ " eh arquivo"
			e2 = fix_name(e)
			if (e != e2):
				os.rename(way+e, way+e2)	
				e = e2
			ff = read_file(way+e)
			if (ff):
				print "    melhor apagar..."
				os.system("rm -f "+ff)

read_dir(thedir)

fh = open(thedir+"/log.txt",'w')
fh.write("started")
for a in delete:
	fh.write("\n"+a)
fh.close()
